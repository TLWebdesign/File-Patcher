<?php
/**
 * @package     Joomla.FilePatcher
 * @author      TLWebdesign (Original)
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;

if (!class_exists('filepatcherInstallerScript'))
{
    class filepatcherInstallerScript
    {
        /**
         * The Joomla versions this patch is allowed to run on.
         */
        private array $supportedVersions = array('5.4.2', '6.0.2');

        /**
         * Whether this installer script already executed in this request.
         */
        private static bool $alreadyRan = false;

        /**
         * Whether this extension should attempt to uninstall itself after running.
         */
        private bool $autoUninstall = true;

        /**
         * Counters for reporting.
         */
        private int $applied = 0;
        private int $failed  = 0;
        private int $missing = 0;

        /**
         * Replacement logic runs during preflight
         */
        public function preflight($type, $parent)
        {
            // Joomla can load installer scripts more than once during install/upgrade.
            // Ensure we only run once per request to avoid duplicate patching/reporting.
            if (self::$alreadyRan) {
                return true;
            }

            $currentVersion = (new Version())->getShortVersion();

            if (!in_array($currentVersion, $this->supportedVersions, true)) {
                Factory::getApplication()->enqueueMessage(
                    'File patcher skipped. Unsupported Joomla version detected: ' . $currentVersion . '. Supported versions: ' . implode(', ', $this->supportedVersions) . '.',
                    'warning'
                );

                // Returning false aborts the installation.
                return false;
            }

            self::$alreadyRan = true;

            $sourcePath = $parent->getParent()->getPath('source') . '/files';
            $sourcePath = realpath($sourcePath);

            // If the package does not contain a files folder, there is nothing to patch.
            if (!$sourcePath || !is_dir($sourcePath)) {
                Factory::getApplication()->enqueueMessage('No patch files folder found in the installation package.', 'warning');
                return true;
            }

            // Define the root path of the Joomla installation
            $rootPath = JPATH_ROOT;

            // Retrieve all the files from the "files" directory and its subdirectories
            $files = Folder::files($sourcePath, '.', true, true);

            // Reset counters for this run.
            $this->applied = 0;
            $this->failed  = 0;
            $this->missing = 0;

            foreach ($files as $file) {
                // Calculate the relative path from the "files" directory.
                $relativePath = ltrim(str_replace($sourcePath, '', $file), '/\\');

                // Determine the destination path in the root directory.
                $destPath = Path::clean($rootPath . '/' . $relativePath);

                // Check if the file exists in the destination path.
                if (is_file($destPath)) {
                    if (!File::copy($file, $destPath, '', false)) {
                        $this->failed++;
                        Factory::getApplication()->enqueueMessage('Failed to replace ' . $relativePath, 'error');
                    } else {
                        $this->applied++;
                        // Log success for audit trail.
                        Factory::getApplication()->enqueueMessage('Patch applied: ' . $relativePath, 'message');
                    }
                } else {
                    $this->missing++;
                    // If the file doesn't exist, we skip it or raise a warning.
                    Factory::getApplication()->enqueueMessage('Target file for patch not found: ' . $relativePath, 'warning');
                }
            }

            return true;
        }

        /**
         * Post-installation cleanup and messaging
         */
        public function postflight($type, $parent)
        {
            // Avoid duplicate reporting if Joomla loads the script multiple times.
            static $reported = false;

            if ($reported) {
                return;
            }

            $reported = true;

            Factory::getApplication()->enqueueMessage(
                'File patcher completed. Applied: ' . $this->applied . ', Failed: ' . $this->failed . ', Missing targets: ' . $this->missing . '.',
                $this->failed > 0 ? 'warning' : 'message'
            );

            if ($this->autoUninstall && in_array($type, array('install', 'update'), true)) {
                $this->uninstallPlugin();
            }
        }

        /**
         * Cleanup: Attempts to remove this extension after it has run.
         */
        private function uninstallPlugin()
        {
            $plugins = $this->findThisPlugin();

            if (empty($plugins)) {
                Factory::getApplication()->enqueueMessage('File patcher cleanup skipped. Extension record was not found.', 'warning');
                return;
            }

            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            foreach ($plugins as $plugin) {
                try {
                    // Remove the extension entry.
                    $query = $db->getQuery(true)
                        ->delete($db->quoteName('#__extensions'))
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $plugin->extension_id);
                    $db->setQuery($query);
                    $db->execute();

                    // Remove the manifest folder.
                    $manifestPath = Path::clean(JPATH_ADMINISTRATOR . '/manifests/files/' . $plugin->element);

                    if (is_dir($manifestPath) && !Folder::delete($manifestPath)) {
                        Factory::getApplication()->enqueueMessage('File patcher cleanup warning. Failed to remove manifest folder: ' . $manifestPath, 'warning');
                    }
                    Factory::getApplication()->enqueueMessage('File patcher has successfully cleaned itself up after installation.', 'message');
                } catch (\Throwable $e) {
                    Factory::getApplication()->enqueueMessage(
                        $plugin->name . ' could not be removed automatically. Please uninstall manually. Error: ' . $e->getMessage(),
                        'warning'
                    );
                }
            }
        }

        /**
        /**
         * Finds the extension IDs for self-removal
         */
        private function findThisPlugin()
        {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select(array('extension_id', 'type', 'name', 'element', 'folder'))
                ->from('#__extensions')
                ->where($db->quoteName('element') . ' = ' . $db->quote('filepatcher'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('file'));
            $db->setQuery($query);

            return $db->loadObjectList() ?: array();
        }
    }
}

# File Patcher for Joomla

## What is this?

This package is a **one-time file patcher** for Joomla installations. It applies specific Joomla core file changes before they are available (or fully backported) in an official Joomla release.

It is intentionally distributed as a **file extension** with an installer script so it can:

- apply patched core files automatically,
- clearly report what was changed,
- and remove itself immediately after execution.

After a successful run, **no extension remains installed**.

---

## Why does this exist?

This file extension can be used to patch joomla files. It was used before to patch a bug i introduced in Joomla 5.4.2 and 6.0.2

---

## Supported Joomla Versions

This patcher **only runs on the Joomla versions specified in the filepatcher.php file**:

If installed on any other Joomla version:

- the patcher will **abort safely**,
- no files will be modified.

This strict version check is intentional to prevent applying patches to unknown or incompatible code bases.

---

## What happens during installation?

1. Joomla starts the installation process.
2. The installer script verifies the Joomla version.
3. The patched core files are copied into place.
4. A summary message is shown, indicating:
   - how many files were applied,
   - how many failed,
   - how many target files were missing.
5. The patcher **removes itself automatically**.
6. The installation completes.

No manual cleanup is required.

---

## Is this safe to use?

Yes, provided that:

- you are running **exactly** one of the supported Joomla versions,
- you understand that this applies **core file changes**,
- you have appropriate backups in place (as recommended for any core modification).

The patcher:

- does not modify the database schema,
- does not install plugins or overrides,
- does not persist after installation.

---

## Removal / Rollback

Because the patcher removes itself automatically:

- there is nothing to uninstall manually.

To roll back the applied changes:

- reinstall the Joomla core files via the administrator interface, or
- restore the affected files from backup.

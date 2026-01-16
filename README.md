# File Patcher for Joomla 5 / 6

## What is this?

This package is a **one-time file patcher** for Joomla installations. It applies specific Joomla core file changes before they are available (or fully backported) in an official Joomla release.

It is intentionally distributed as a **file extension** with an installer script so it can:

- apply patched core files automatically,
- clearly report what was changed,
- and remove itself immediately after execution.

After a successful run, **no extension remains installed**.

---

## Why does this exist?

This patcher is directly related to the following Joomla core pull request:

- https://github.com/joomla/joomla-cms/pull/46431

That pull request was created to improve e‑mail deliverability. However, it introduced a bug (see https://github.com/joomla/joomla-cms/issues/46419) as well as an unintentional behavioural change.

Specifically, when using external mail servers such as Exchange, Microsoft 365, or Gmail, it became impossible in some cases (for example in `com_contact`) to send e‑mail where the **sender and recipient addresses are the same**.

While enforcing different sender and recipient addresses can be considered good practice, introducing such a behavioural change in a **point release** is not appropriate. For that reason, the related core change is being reverted upstream.

In the meantime, this patcher provides administrators with an **easy and safe way to revert the affected core file**, without having to manually edit files on their site.

---

## Supported Joomla Versions

This patcher **only runs on the following exact Joomla versions**:

- **5.4.2**
- **6.0.2**

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

## About the “JSON Parse error” warning

On some systems, you may briefly see a message such as:

```
JSON Parse error: Unrecognized token '<'
```

or a warning mentioning:

```
libraries/src/Installer/Adapter/FileAdapter.php
```

### Important: this does **not** mean the patch failed

This warning occurs because:

- Joomla’s extension installer uses AJAX and JSON responses,
- and PHP warnings (even non‑fatal ones) can sometimes be included in the response output.

### What to do if you see this

- **Simply refresh the page**.
- Review the administrator messages after the reload.

If you see messages such as:

- “Security patch applied: …”
- “File patcher completed. Applied: X, Failed: 0”

then the patch was applied successfully.

No changes are rolled back, and no partial state is left behind.

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

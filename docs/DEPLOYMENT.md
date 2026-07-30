# CodeMwana 3.0 deployment guide

## XAMPP or local Apache

1. Copy the project into `C:\xampp\htdocs\CodeMwana`.
2. Start Apache and MySQL.
3. Create an empty `codemwana` MySQL database using `utf8mb4_unicode_ci`.
4. Copy `.env.example` to `.env` and enter the correct URL and database credentials.
5. Open `/CodeMwana/setup.php`.
6. Provide the real organisation, support email and first-administrator credentials.
7. Complete installation and confirm that the administrator can sign in.
8. Delete or rename `setup.php`.
9. Open `/CodeMwana/system-status.php` and verify the installation and schema version.

The application checks the database and `storage/installed.lock`. A completed installation continues normally after `setup.php` is removed. A database outage produces a status response instead of an installer redirect.

## Shared hosting

1. Create a MySQL database and a dedicated database user.
2. Grant the user only the privileges needed for the CodeMwana database.
3. Upload the project to the intended document root.
4. Copy `.env.example` to `.env` and configure the HTTPS URL and database credentials.
5. Select PHP 8.1 or newer and enable PDO MySQL and cURL.
6. Open `setup.php`, create the first administrator and install the database content.
7. Remove `setup.php` after successful installation.
8. Verify registration, login, enrolment, quizzes, multi-file project saving and staff operations.

## Optional isolated code runner

Browser languages do not require a server runner. Python, PHP, Go, C and C++ require a trusted Piston-compatible service.

```env
CODE_RUNNER_URL=https://runner.example.org
CODE_RUNNER_TOKEN=
CODE_RUNNER_TIMEOUT=15
```

Do not install compilers into the public PHP web process or pass learner code to `exec`, `shell_exec`, `system` or similar functions. Keep code execution isolated from the application and database hosts.

## Required production values

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example/codemwana
DB_DRIVER=mysql
```

## Production checklist

- Use HTTPS.
- Protect `.env` and the storage directory.
- Remove `setup.php` after installation.
- Retain `storage/installed.lock`.
- Use a dedicated least-privilege database user.
- Configure the code runner only over HTTPS.
- Create individual staff accounts.
- Review curriculum and assessment publication status.
- Test learner, teacher and administrator permissions.
- Back up database records and project workspaces.
- Test 360px, 768px, 1024px and large desktop widths.
- Test Code Lab in portrait and landscape orientation.
- Review privacy, retention and account-deletion procedures.

## Updating

1. Back up the database and files.
2. Deploy the changed source.
3. Open the application; the migration service upgrades the schema.
4. Open `system-status.php`.
5. Run `php tests/smoke.php`.
6. Test login, project creation, all browser preview modes and one configured remote language.
7. Clear the service-worker cache when static asset versions change.

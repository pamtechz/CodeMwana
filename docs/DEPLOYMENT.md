# CodeMwana deployment guide

## XAMPP or local Apache

1. Copy the project into the web root, for example `C:\xampp\htdocs\CodeMwana`.
2. Start Apache and MySQL.
3. Create an empty `codemwana` MySQL database using `utf8mb4_unicode_ci`.
4. Copy `.env.example` to `.env` and enter the correct URL and database credentials.
5. Open `/CodeMwana/setup.php`.
6. Provide the organisation name and real first-administrator credentials.
7. Complete installation and confirm that the administrator can sign in.
8. Remove or rename `setup.php`.

## cPanel, Hostinger or compatible shared hosting

1. Create a MySQL database and a dedicated database user.
2. Grant that user only the privileges required for the CodeMwana database.
3. Upload the project into `public_html/codemwana` or the intended document root.
4. Copy `.env.example` to `.env` and configure the HTTPS URL, environment and database credentials.
5. Select PHP 8.1 or newer and enable PDO MySQL.
6. Open `/codemwana/setup.php`, create the first administrator and install the curriculum.
7. Remove `setup.php` immediately after a successful installation.
8. Sign in and verify learner registration, enrolment, assessment submission, project saving and staff operations.

## Required production values

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example/codemwana
DB_DRIVER=mysql
```

Use the host-provided database host, port, name, username and password.

## Production checklist

- Use HTTPS on every page.
- Keep `.env` inaccessible to the public web.
- Remove `setup.php` after installation.
- Use a dedicated least-privilege database user.
- Create individual teacher and administrator accounts; do not share credentials.
- Keep registration closed until the platform is ready for learners.
- Review curriculum and assessment publication status before launch.
- Test role restrictions for learner, teacher and administrator accounts.
- Schedule database and uploaded-code backups.
- Test at phone, tablet and desktop widths.
- Publish institution-approved privacy, retention and account-deletion procedures.
- Review PHP and web-server logs without exposing them publicly.

## Updating the application

1. Back up the database and current files.
2. Deploy changed source files.
3. Compare schema files before applying database changes.
4. Run `php tests/smoke.php`.
5. Test critical operations using a non-production learner account.
6. Clear the browser service-worker cache when static asset versions change.

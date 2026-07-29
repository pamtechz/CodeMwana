# Deployment guide

## Option A: XAMPP demonstration

Follow the root `README.md` installation steps. This is the fastest way to demonstrate the app during marking.

## Option B: Hostinger or cPanel shared hosting

1. Create a MySQL database and database user in the hosting control panel.
2. Upload all project files into a subdirectory such as `public_html/codemwana`.
3. Copy `.env.example` to `.env` and enter the hosted URL and database credentials.
4. Ensure PHP 8.1 or newer and the PDO MySQL extension are enabled.
5. Open `/codemwana/setup.php` once to create and seed the tables.
6. Delete `setup.php` and restrict access to `.env` and SQL files.
7. Enable HTTPS and test registration, login, quiz submission and project saving.

## Production checklist

- Replace demonstration passwords.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a unique MySQL user with access only to the CodeMwana database.
- Keep `.env` outside public access where the host permits it.
- Back up the database before content updates.
- Test at 320 px, 768 px and desktop widths.
- Validate representative pages using the W3C HTML and CSS validators.
- Publish a formal privacy notice approved by the institution before enrolling real children.

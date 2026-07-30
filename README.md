# CodeMwana

**ICT4410 Question 11:** A web application that teaches children basic programming skills and concepts.

CodeMwana is a responsive, database-backed PHP learning platform built for real learner, teacher and administrator operations. The public website, curriculum, assessments, progress records, projects, announcements and platform settings all read from relational database records rather than placeholder content.

## Production-style capabilities

- Secure learner registration and username/email authentication
- Login rate limiting, strong password rules and secure session handling
- Database-driven branding, homepage features, learning paths and lessons
- Course enrolment, ordered lesson progression and saved completion status
- Database-managed quizzes with answer explanations and best-score tracking
- Safe MwanaCode interpreter with variables, decisions, loops and turtle drawing
- Project CRUD with version history and ownership validation
- Learner dashboard, progress analytics, badges and optional leaderboard
- Teacher reporting, difficult-lesson insight and announcement management
- Administrator user, role, status and password-reset operations
- Learning-path, lesson and assessment-question CRUD
- MySQL/MariaDB and SQLite schemas
- Responsive accessible interface, PWA assets and offline fallback
- Prepared statements, password hashing, CSRF protection, output escaping and audit logging

## Requirements

- PHP 8.1 or newer
- PDO MySQL for production MySQL/MariaDB, or PDO SQLite for local SQLite use
- MySQL 8 / MariaDB 10.4 or newer when using MySQL
- Apache or another PHP-capable web server

## XAMPP installation

1. Copy the `CodeMwana` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Create a database named `codemwana` with `utf8mb4_unicode_ci` collation.
4. Copy `.env.example` to `.env`.
5. Confirm the local values:

```env
APP_URL=http://localhost/CodeMwana
APP_ENV=production
APP_DEBUG=false
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=codemwana
DB_USERNAME=root
DB_PASSWORD=
```

6. Open `http://localhost/CodeMwana/setup.php`.
7. Enter the real platform organisation and first-administrator account details.
8. Run the installation. The installer creates the schema, settings, curriculum, questions and first administrator.
9. Delete or rename `setup.php` after a successful installation.
10. Sign in with the administrator account created during installation.

No fixed demonstration passwords or fake learner accounts are seeded.

## Shared hosting

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) for a cPanel/Hostinger-style deployment checklist.

## Main directories

- `app/` — configuration, database, authentication, settings and learning services
- `assets/` — external CSS, JavaScript and image assets
- `database/` — MySQL/SQLite schemas and curriculum installer
- `partials/` — shared public and authenticated layouts
- `teacher/` — reporting and announcement operations
- `admin/` — accounts, curriculum, assessments and platform settings
- `api/` — authenticated JSON operations
- `docs/` — deployment, testing and project documentation
- `tests/` — static smoke checks

## Safe interpreter

MwanaCode does not use JavaScript `eval()`. It parses only supported educational commands and enforces maximum loop and program-step limits.

## Validation

Run:

```bash
php tests/smoke.php
```

The smoke test validates required files, PHP syntax, JavaScript syntax where Node.js is available, prohibited placeholder attributes and key database/schema declarations.

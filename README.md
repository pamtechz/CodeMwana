# CodeMwana 3.3

**ICT4410 Question 11:** A web application that teaches children basic programming skills and concepts.

CodeMwana is a responsive, database-backed PHP learning platform for real learner, teacher and administrator operations. Curriculum, assessments, progress, projects, announcements, platform settings and the Code Lab language catalogue persist in MySQL/MariaDB or SQLite.

## Code Lab

Code Lab includes a guided MwanaCode workspace and ten mainstream programming workspaces:

1. HTML
2. CSS
3. JavaScript
4. Python
5. PHP
6. React
7. Next.js
8. Go
9. C
10. C++

Each workspace supports database-backed starter files, multiple project files, standard input, project ownership, version history and execution records.

### Execution model

- **MwanaCode:** controlled in-browser interpreter with step and loop limits.
- **HTML and CSS:** live sandboxed page preview.
- **JavaScript:** isolated browser execution with captured console messages.
- **React and Next.js:** sandboxed component preview. Server-only Next.js features require a full Node deployment.
- **Python:** free WebAssembly/WASI execution in the learner's browser with no API key and no local Python installation.
- **PHP:** free WebAssembly/WASI execution in the learner's browser with no API key and no local PHP runtime installation for learner programs.
- **Go, C and C++:** optional remote isolated execution through a configured Piston-compatible runner. Without a runner, these workspaces can still be written and saved.

The first Python run downloads approximately 26 MB and the first PHP run approximately 13 MB from the Codapi browser-runtime CDN. Modern browsers cache those runtime files, so subsequent runs are much faster.

CodeMwana never runs untrusted learner code directly through PHP on the application server.

## Platform capabilities

- Username or email authentication, rate limiting and secure sessions
- Strong passwords, role-based access, CSRF protection and audit logging
- Intelligent installation state and automatic schema migrations
- Database-driven branding, homepage content, curriculum and languages
- Course enrolment, ordered lessons, quizzes and persistent progress
- Multi-file project CRUD, version history and execution logs
- Learner dashboard, badges, reporting and optional leaderboard
- Teacher performance reports and announcement management
- Administrator user, curriculum, assessment and platform operations
- Dedicated responsive curriculum path and lesson editor pages
- Word-style curriculum lesson authoring with local draft protection
- Compact responsive layouts for phones, tablets, laptops and desktops
- Progressive Web App assets and offline fallback

## Requirements

- PHP 8.1 or newer
- PDO MySQL for MySQL/MariaDB, or PDO SQLite for SQLite
- MySQL 8 / MariaDB 10.4 or newer when using MySQL
- Apache or another PHP-capable web server
- A modern browser with WebAssembly support
- Internet access for the initial Python or PHP browser-runtime download
- Optional isolated code runner only when Go, C and C++ execution is required

## XAMPP installation

1. Copy the project into `C:\xampp\htdocs\CodeMwana`.
2. Start Apache and MySQL.
3. Create a database named `codemwana` using `utf8mb4_unicode_ci`.
4. Copy `.env.example` to `.env`.
5. Configure `APP_URL` and the database values.
6. Open `http://localhost/CodeMwana/setup.php`.
7. Enter the real organisation, support address and first-administrator details.
8. Complete installation and sign in.
9. Delete `setup.php` after installation.

CodeMwana records an installation lock and verifies the database. Removing `setup.php` does not cause a redirect loop. When the database is temporarily unavailable, the application displays operational diagnostics instead of reopening setup.

No fixed demonstration passwords or fake learner accounts are seeded.

## Optional external compiler

No runner configuration is required for MwanaCode, HTML, CSS, JavaScript, React, Next.js, Python or PHP.

Configure an isolated Piston-compatible runner only when learners must execute Go, C or C++:

```env
CODE_RUNNER_URL=https://runner.example.org
CODE_RUNNER_TOKEN=
CODE_RUNNER_TIMEOUT=15
```

When `CODE_RUNNER_URL` is empty, Go, C and C++ display a clear external-compiler message. Their projects remain editable and can still be saved.

## Updating an existing installation

1. Back up the database and files.
2. Deploy the current CodeMwana source.
3. Preserve the existing `.env` file.
4. Open the application normally.
5. Open `system-status.php` to verify installation and Code Lab readiness.
6. Run `php tests/smoke.php`.
7. Hard-refresh the browser or update the service worker when an older Code Lab remains cached.

## Main directories

- `app/` — configuration, database, installation, migration, authentication, language and learning services
- `assets/` — responsive CSS, Code Lab JavaScript, no-install browser runners and image assets
- `database/` — MySQL/SQLite schemas and database seed content
- `partials/` — shared public and authenticated layouts
- `teacher/` — reporting and announcement operations
- `admin/` — accounts, curriculum, assessments and platform settings
- `api/` — authenticated project-save and optional external code-run operations
- `docs/` — deployment and testing documentation
- `tests/` — static smoke checks

## Validation

```bash
php tests/smoke.php
```

The smoke test validates required files, PHP and JavaScript syntax, no-install Python/PHP runtime declarations, responsive assets, curriculum pages, intelligent installation declarations, ten database language definitions and the required database tables.

# CodeMwana 3.4

**ICT4410 Question 11:** A web application that teaches children basic programming skills and concepts.

CodeMwana is a responsive, database-backed PHP learning platform for learner, teacher and administrator operations. Curriculum, assessments, progress, projects, announcements, platform settings and Code Lab language definitions persist in MySQL/MariaDB or SQLite.

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

Each workspace supports database-backed starter files, project ownership, standard input, version history and execution records.

### Execution model

- **MwanaCode:** controlled browser interpreter with step and loop limits.
- **HTML and CSS:** sandboxed browser preview.
- **JavaScript:** isolated browser execution with captured console messages.
- **React and Next.js:** sandboxed component preview. Server-only Next.js features require a complete Node deployment.
- **Python, PHP, Go, C and C++:** one synchronized managed editor. The CodeMwana Run button submits to JDoodle first. When the primary service is unavailable, out of credits, rate-limited or not configured, the same visible editor executes through the embedded workspace automatically.

The local CodeMwana textarea and duplicate file sidebar are hidden while a managed language is selected. Project data remains synchronized with CodeMwana so Save, project ownership, version history and run logging continue to work.

JDoodle credentials stay on the server and are never included in browser JavaScript. The OneCompiler workspace loads as a normal cross-origin iframe, matching its official embed model. CodeMwana sends and accepts editor messages only through the expected `https://onecompiler.com` origin and the exact iframe window. Browser same-origin protections prevent the external frame from reading the CodeMwana document.

When the standard-input field is completely empty, CodeMwana supplies one blank input line to managed execution. This allows beginner patterns such as `input().strip() or 'Learner'`, `fgets(STDIN)` and `getline()` to receive an empty value instead of immediately reaching EOF.

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
- Word-style lesson authoring with local draft protection
- Responsive layouts for phones, tablets, laptops and desktops
- Progressive Web App assets and offline fallback

## Requirements

- PHP 8.1 or newer
- PDO MySQL for MySQL/MariaDB, or PDO SQLite for SQLite
- MySQL 8 / MariaDB 10.4 or newer when using MySQL
- Apache or another PHP-capable web server
- A modern browser
- Internet access for managed-language editing and execution
- Optional JDoodle credentials for primary managed execution

## XAMPP installation

1. Copy the project into `C:\xampp\htdocs\CodeMwana`.
2. Start Apache and MySQL.
3. Create a database named `codemwana` using `utf8mb4_unicode_ci`.
4. Copy `.env.example` to `.env`.
5. Configure `APP_URL`, database values and optional JDoodle credentials.
6. Open `http://localhost/CodeMwana/setup.php`.
7. Enter the organisation, support address and first-administrator details.
8. Complete installation and sign in.
9. Delete `setup.php` after installation.

CodeMwana records an installation lock and verifies the database. Removing `setup.php` does not cause a redirect loop. When the database is temporarily unavailable, the application displays diagnostics instead of reopening setup.

No fixed demonstration passwords or fake learner accounts are seeded.

## Managed execution configuration

```env
CODE_RUNNER_PROVIDER=jdoodle
CODE_RUNNER_TIMEOUT=20

JDOODLE_API_URL=https://api.jdoodle.com/v1/execute
JDOODLE_MULTI_FILE_URL=https://api.jdoodle.com/v1/engine/execute-api-multifile
JDOODLE_CLIENT_ID=
JDOODLE_CLIENT_SECRET=

JDOODLE_PYTHON_VERSION_INDEX=6
JDOODLE_PHP_VERSION_INDEX=6
JDOODLE_GO_VERSION_INDEX=6
JDOODLE_C_VERSION_INDEX=7
JDOODLE_CPP_VERSION_INDEX=3

ONECOMPILER_EMBED_URL=https://onecompiler.com/embed
```

When JDoodle credentials are absent or the primary call cannot be completed, CodeMwana automatically runs the program through the already visible managed workspace. Learner-facing messages remain provider-neutral; provider and fallback reasons are retained in administrator activity logs.

## Updating an existing installation

1. Back up the database and files.
2. Deploy the current CodeMwana source.
3. Preserve the existing `.env` file and add the managed execution variables above.
4. Open the application normally.
5. Open `system-status.php` to verify installation and Code Lab readiness.
6. Run `php tests/smoke.php`.
7. Unregister the old service worker or hard-refresh when an older Code Lab remains cached.

The current service-worker cache is `codemwana-static-v9`. It includes the frame-compatibility loader and no longer caches the retired Codapi browser runner.

## Main directories

- `app/` — configuration, database, installation, migration, authentication, language and learning services
- `assets/` — responsive CSS, Code Lab JavaScript and image assets
- `database/` — MySQL/SQLite schemas and database seed content
- `partials/` — shared public and authenticated layouts
- `teacher/` — reporting and announcement operations
- `admin/` — accounts, curriculum, assessments and platform settings
- `api/` — authenticated project-save and managed code-run operations
- `docs/` — deployment and testing documentation
- `tests/` — static smoke checks

## Validation

```bash
php tests/smoke.php
```

The smoke test validates PHP and JavaScript syntax when the relevant runtimes are available, ten language definitions, the five-language managed editor, JDoodle blank-input handling, OneCompiler origin compatibility, embedded fallback, responsive assets, installation state and required database tables.

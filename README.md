# CodeMwana 3.5

CodeMwana is a responsive PHP learning platform for children, schools and young creators. It combines guided lessons, assessments, project work, progress tracking and role-based teaching operations.

## Learning workspaces

Code Lab provides MwanaCode and ten mainstream programming workspaces:

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

MwanaCode and browser projects run through controlled browser previews. Python, PHP, C, C++ and Go share one synchronized managed editor. Source files, project ownership, standard input, saved versions and execution records remain connected to the learner account.

For managed languages, the server submits code to the configured compiler API first. When that service cannot complete a request, the visible embedded workspace becomes the execution fallback without opening a second editor.

Python source receives a server-side input compatibility prelude before execution. It preserves learner source in storage while preventing `input()` from raising `EOFError` when no standard-input bytes are supplied. The prelude is inserted after any `__future__` imports.

Compiler credentials remain server-side. Learner code is never executed directly by the public PHP process.

## Public production behaviour

Release 3.5 separates public guidance from operational information:

- `help.php` reads the current site name, registration state, support address, published paths and active languages.
- About, Privacy and landing content use learner-facing language rather than deployment or database terminology.
- System diagnostics require an administrator account.
- The installer redirects to the normal site after installation.
- Public service failures are generic; technical details are written only to private server logs.
- Software versions are not displayed in the shared public shell.
- Authenticated and utility pages are marked `noindex,nofollow`.

## Security controls

- Password hashing, secure sessions and session ID regeneration
- Login rate limiting
- CSRF protection on state-changing requests
- Role-based access controls
- Escaped output and lesson-content sanitisation
- PHP-owned Content Security Policy
- HSTS on HTTPS deployments
- MIME sniffing, framing, referrer and permissions restrictions
- No-store caching for account and application responses
- Direct HTTP access blocked for application internals, configuration, storage, tests and database files
- Compiler and fallback hosts restricted to approved HTTPS origins
- Compiler paths and provider names removed from learner-visible output

No design can guarantee that a public application will never be attacked. These controls reduce exposure and establish safer production defaults; hosting updates, backups, monitoring and access reviews remain operational requirements.

## Requirements

- PHP 8.1 or newer
- PDO MySQL/MariaDB or PDO SQLite
- MySQL 8, MariaDB 10.4 or newer when using MySQL
- Apache with `.htaccess` support, or equivalent web-server rules
- A modern browser
- Internet access for managed-language editing and execution

## Installation

1. Copy the project into the web root.
2. Start the web server and database service.
3. Create the application database.
4. Copy `.env.example` to `.env`.
5. Configure `APP_URL`, database credentials and managed execution credentials.
6. Open `setup.php` once.
7. Create the first administrator account.
8. Sign in and verify the platform.

After installation, `setup.php` redirects to the normal site. It may also be removed from the deployed server as an additional operational precaution.

## Managed execution configuration

```env
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

The application validates the configured API host and fallback host before use. Legacy arbitrary runner URL and token settings are no longer supported.

## Updating an installation

1. Back up the database and deployed files.
2. Pull or deploy the current source.
3. Preserve the existing `.env` values and remove retired runner variables.
4. Restart the PHP/Apache service.
5. Open the application once so migration `3.5.0` can update public content.
6. Sign in as an administrator and review `system-status.php`.
7. Clear the previous service worker when old Code Lab JavaScript remains cached.

Current service-worker cache: `codemwana-static-v9`.

## Validation

Run both checks before deployment:

```bash
php tests/smoke.php
php tests/python_input_guard.php
```

The repository also contains `.github/workflows/quality.yml`, which runs these checks for pushes to `main` and pull requests.

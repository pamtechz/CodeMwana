# CodeMwana isolated code runner

CodeMwana deliberately does not execute learner PHP, Python, Go, C or C++ inside Apache/XAMPP. Those languages are sent to a separate Piston sandbox with process, memory and time limits.

## Windows and XAMPP

Requirements:

- Docker Desktop running Linux containers
- Git
- Node.js and npm
- The current CodeMwana project with a valid `.env`

From PowerShell in the CodeMwana project folder, run:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\runner\setup-windows.ps1
```

The script:

1. Starts the official Piston container as `codemwana-piston`.
2. Binds the API to `127.0.0.1:2000` only.
3. Installs Python, PHP, Go, C and C++ runtimes.
4. Writes `CODE_RUNNER_URL=http://127.0.0.1:2000` to `.env`.
5. Runs a verification program.

After completion, open:

```text
http://localhost/CodeMwana/system-status.php
```

Then open Code Lab and run a PHP, Python, Go, C or C++ project.

## Useful commands

```powershell
# View the container
docker ps --filter name=codemwana-piston

# View logs
docker logs codemwana-piston

# Stop the runner
docker stop codemwana-piston

# Start it again
docker start codemwana-piston

# List installed runtimes
Invoke-RestMethod http://127.0.0.1:2000/api/v2/runtimes
```

## Production deployment

Do not expose port 2000 directly to the public internet. Put the runner on a private network, require an authorization token or reverse-proxy authentication, and restrict access so only the CodeMwana application server can call it.

Set the application environment:

```env
CODE_RUNNER_URL=http://private-runner:2000
CODE_RUNNER_TOKEN=
CODE_RUNNER_TIMEOUT=15
```

Browser workspaces—MwanaCode, HTML, CSS, JavaScript, React and compatible Next.js previews—continue to work when the remote runner is unavailable.

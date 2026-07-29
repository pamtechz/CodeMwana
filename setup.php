<?php
require_once __DIR__ . '/app/bootstrap.php';

$installed = false;
$message = null;
$error = null;
$driver = (string) config('database.driver', 'mysql');
$availableDrivers = PDO::getAvailableDrivers();

try {
    $pdo = Database::connection();
    try {
        $installed = (bool) $pdo->query('SELECT 1 FROM users LIMIT 1');
    } catch (Throwable) {
        $installed = false;
    }

    if (is_post()) {
        verify_csrf();
        $schemaPath = base_path('database/schema_' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql');
        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('The database schema file could not be read.');
        }
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema);
        foreach ($statements ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
        require_once base_path('database/seed.php');
        seed_database($pdo);
        $installed = true;
        $message = 'CodeMwana was installed successfully. Remove or rename setup.php before publishing the project.';
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$pageTitle = 'Project setup';
$bodyClass = 'content-page';
require base_path('partials/header.php');
?>
<section class="page-hero admin-hero"><div class="container"><span class="eyebrow light">One-time installer</span><h1>Set up CodeMwana</h1><p>Create the database tables and demonstration learning content.</p></div></section>
<section class="section"><div class="container narrow">
    <?php if ($message): ?><div class="alert" style="background:#e6f7ee;border:1px solid #a9ddc2;color:#185b42"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><strong>Database connection or setup failed.</strong><br><?= e($error) ?></div><?php endif; ?>
    <div class="settings-card">
        <h2>Environment check</h2>
        <div class="responsive-table"><table><tbody>
            <tr><th>PHP version</th><td><?= e(PHP_VERSION) ?></td><td><?= version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Ready' : 'Upgrade required' ?></td></tr>
            <tr><th>Configured driver</th><td><?= e($driver) ?></td><td><?= in_array($driver === 'sqlite' ? 'sqlite' : 'mysql', $availableDrivers, true) ? 'Available' : 'PDO driver missing' ?></td></tr>
            <tr><th>Database tables</th><td><?= $installed ? 'Detected' : 'Not detected' ?></td><td><?= $installed ? 'Ready' : 'Installation required' ?></td></tr>
        </tbody></table></div>
        <h2>Before continuing</h2>
        <ol>
            <li>Copy <code>.env.example</code> to <code>.env</code>.</li>
            <li>For XAMPP, create a MySQL database named <code>codemwana</code> in phpMyAdmin.</li>
            <li>Confirm the database username and password in <code>.env</code>.</li>
        </ol>
        <?php if (!$installed): ?>
        <form method="post"><?= csrf_field() ?><button class="button button-large" type="submit">Create tables and sample data</button></form>
        <?php else: ?>
        <p><strong>The application is installed.</strong></p><a class="button" href="<?= e(url('login.php')) ?>">Open sign-in page</a>
        <?php endif; ?>
    </div>
</div></section>
<?php require base_path('partials/footer.php'); ?>

<?php
require_once __DIR__ . '/app/bootstrap.php';

$driver = (string) config('database.driver', 'mysql');
$availableDrivers = PDO::getAvailableDrivers();
$errors = [];
$installed = false;
$connectionError = null;

try {
    $pdo = Database::connection();
    $installed = Database::tableExists('users') && (int) Database::scalar('SELECT COUNT(*) FROM users') > 0;
} catch (Throwable $exception) {
    $connectionError = $exception->getMessage();
}

if (is_post() && !$installed && !$connectionError) {
    verify_csrf();
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
        'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
        'organisation' => trim((string) ($_POST['organisation'] ?? '')),
        'platform_name' => trim((string) ($_POST['platform_name'] ?? '')),
        'support_email' => strtolower(trim((string) ($_POST['support_email'] ?? ''))),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
    ];
    set_old(array_diff_key($data, ['password' => true, 'password_confirmation' => true]));
    if (mb_strlen($data['name']) < 3) $errors['name'] = 'Enter the administrator’s full name.';
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $data['username'])) $errors['username'] = 'Use 3–40 lowercase letters, numbers, dots, dashes or underscores.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid administrator email address.';
    if (mb_strlen($data['organisation']) < 2) $errors['organisation'] = 'Enter the school or organisation name.';
    if (mb_strlen($data['platform_name']) < 3 || mb_strlen($data['platform_name']) > 80) $errors['platform_name'] = 'Enter a platform name between 3 and 80 characters.';
    if (!filter_var($data['support_email'], FILTER_VALIDATE_EMAIL)) $errors['support_email'] = 'Enter the operational support email address.';
    if (strlen($data['password']) < 10 || !preg_match('/[A-Z]/', $data['password']) || !preg_match('/[a-z]/', $data['password']) || !preg_match('/\d/', $data['password'])) {
        $errors['password'] = 'Use at least 10 characters with uppercase, lowercase and a number.';
    }
    if ($data['password'] !== $data['password_confirmation']) $errors['password_confirmation'] = 'The passwords do not match.';

    if (!$errors) {
        try {
            $schemaPath = base_path('database/schema_' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql');
            $schema = file_get_contents($schemaPath);
            if ($schema === false) throw new RuntimeException('The database schema could not be read.');
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [] as $statement) {
                $statement = trim($statement);
                if ($statement !== '') $pdo->exec($statement);
            }
            require_once base_path('database/seed.php');
            seed_database($pdo, $data);
            Installation::markInstalled(Migrator::VERSION);
            clear_old();
            flash('success', 'CodeMwana is ready. Sign in with the administrator account you created.');
            redirect('login.php');
        } catch (Throwable $exception) {
            $errors['setup'] = config('app.debug') ? $exception->getMessage() : 'Installation could not be completed. Check the database configuration and try again.';
        }
    }
}

$pageTitle = 'Platform installation';
$bodyClass = 'setup-page';
require base_path('partials/header.php');
?>
<section class="setup-shell">
    <div class="setup-intro">
        <span class="eyebrow">CodeMwana 3.0</span>
        <h1>Install the learning platform</h1>
        <p>This installer creates the database structure, curriculum, badges, platform settings and the first administrator account.</p>
        <div class="setup-checks">
            <div><?= icon('check-circle') ?><span><strong>PHP <?= e(PHP_VERSION) ?></strong><small><?= version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Version requirement met' : 'PHP 8.1 or newer is required' ?></small></span></div>
            <div><?= icon('check-circle') ?><span><strong><?= e(strtoupper($driver)) ?> database</strong><small><?= in_array($driver === 'sqlite' ? 'sqlite' : 'mysql', $availableDrivers, true) ? 'PDO driver available' : 'Required PDO driver is missing' ?></small></span></div>
            <div><?= icon('shield-check') ?><span><strong>Secure administrator creation</strong><small>No fixed production credentials are seeded</small></span></div>
        </div>
    </div>
    <div class="setup-card">
        <?php if ($installed): ?>
            <div class="state-inline success"><?= icon('check-circle') ?><div><h2>CodeMwana is already installed</h2><p>Database users and curriculum data were detected.</p><a class="button" href="<?= e(url('login.php')) ?>">Go to sign in</a></div></div>
        <?php elseif ($connectionError): ?>
            <div class="state-inline danger"><?= icon('alert-circle') ?><div><h2>Database connection failed</h2><p><?= e($connectionError) ?></p><p>Update the values in <code>.env</code>, then reload this page.</p></div></div>
        <?php else: ?>
            <div class="card-heading"><span class="step-badge">1</span><div><h2>Create the platform administrator</h2><p>This account controls users, curriculum and platform settings.</p></div></div>
            <?php if (isset($errors['setup'])): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($errors['setup']) ?></span></div><?php endif; ?>
            <form method="post" class="form-stack" data-progress-form>
                <?= csrf_field() ?>
                <div class="form-grid two">
                    <div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?= old('name') ?>" autocomplete="name" required><?= validation_error($errors, 'name') ?></div>
                    <div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= old('username') ?>" autocomplete="username" required><?= validation_error($errors, 'username') ?></div>
                </div>
                <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required><?= validation_error($errors, 'email') ?></div>
                <div class="form-grid two"><div class="field"><label for="organisation">School or organisation</label><input id="organisation" name="organisation" value="<?= old('organisation') ?>" autocomplete="organization" required><?= validation_error($errors, 'organisation') ?></div><div class="field"><label for="platform_name">Platform name</label><input id="platform_name" name="platform_name" value="<?= old('platform_name', 'CodeMwana') ?>" required><?= validation_error($errors, 'platform_name') ?></div></div>
                <div class="field"><label for="support_email">Support email</label><input id="support_email" name="support_email" type="email" value="<?= old('support_email') ?>" autocomplete="email" required><?= validation_error($errors, 'support_email') ?></div>
                <div class="form-grid two">
                    <div class="field"><label for="password">Password</label><div class="password-field"><input id="password" name="password" type="password" autocomplete="new-password" required data-password-strength><button type="button" data-password-toggle="password">Show</button></div><?= validation_error($errors, 'password') ?><small class="field-hint">At least 10 characters, including uppercase, lowercase and a number.</small></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><div class="password-field"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Show</button></div><?= validation_error($errors, 'password_confirmation') ?></div>
                </div>
                <button class="button button-large button-full" type="submit" data-submit-button><span>Install CodeMwana</span><?= icon('arrow-right') ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

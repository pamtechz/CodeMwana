<?php
require_once __DIR__ . '/app/bootstrap.php';

// The installer is a one-time operation. Once an account-backed installation
// exists, never render installation details on a public request.
if (Installation::installed()) redirect('index.php');

$driver = (string) config('database.driver', 'mysql');
$availableDrivers = PDO::getAvailableDrivers();
$errors = [];
$connectionError = null;

try {
    $pdo = Database::connection();
} catch (Throwable $exception) {
    error_log('CodeMwana setup connection failure: ' . $exception->getMessage());
    $connectionError = 'The required service could not be reached. Review the private hosting configuration and try again.';
}

if (is_post() && !$connectionError) {
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
            if ($schema === false) throw new RuntimeException('The application structure could not be read.');
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [] as $statement) {
                $statement = trim($statement);
                if ($statement !== '') $pdo->exec($statement);
            }
            require_once base_path('database/seed.php');
            seed_database($pdo, $data);

            Database::reset();
            $verifiedAdministrator = Database::fetch(
                "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND role = 'admin' AND status = 'active' LIMIT 1",
                [$data['email']]
            );
            if (!Database::tableExists('users') || !Database::tableExists('site_settings') || !$verifiedAdministrator) {
                throw new RuntimeException('Installation data could not be verified.');
            }

            Installation::markInstalled(Migrator::VERSION);
            clear_old();
            flash('success', 'The learning platform is ready. Sign in with the administrator account you created.');
            redirect('login.php');
        } catch (Throwable $exception) {
            error_log('CodeMwana setup failure: ' . $exception->getMessage());
            $errors['setup'] = 'Installation could not be completed. Review the private hosting configuration and try again.';
        }
    }
}

$pageTitle = 'Platform installation';
$bodyClass = 'setup-page';
require base_path('partials/header.php');
?>
<section class="setup-shell">
    <div class="setup-intro">
        <span class="eyebrow">Initial setup</span>
        <h1>Prepare the learning platform</h1>
        <p>This one-time process creates the application data and the first administrator account.</p>
        <div class="setup-checks">
            <div><?= icon('check-circle') ?><span><strong>Application runtime</strong><small><?= version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Requirement met' : 'A newer runtime is required' ?></small></span></div>
            <div><?= icon('check-circle') ?><span><strong>Data service</strong><small><?= in_array($driver === 'sqlite' ? 'sqlite' : 'mysql', $availableDrivers, true) ? 'Available' : 'Required connection driver is missing' ?></small></span></div>
            <div><?= icon('shield-check') ?><span><strong>Administrator creation</strong><small>Create a unique account for the organisation</small></span></div>
        </div>
    </div>
    <div class="setup-card">
        <?php if ($connectionError): ?>
            <div class="state-inline danger"><?= icon('alert-circle') ?><div><h2>Required service unavailable</h2><p><?= e($connectionError) ?></p></div></div>
        <?php else: ?>
            <div class="card-heading"><span class="step-badge">1</span><div><h2>Create the platform administrator</h2><p>This account manages users, learning content and platform settings.</p></div></div>
            <?php if (isset($errors['setup'])): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($errors['setup']) ?></span></div><?php endif; ?>
            <form method="post" class="form-stack" data-progress-form>
                <?= csrf_field() ?>
                <div class="form-grid two">
                    <div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?= old('name') ?>" autocomplete="name" required><?= validation_error($errors, 'name') ?></div>
                    <div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= old('username') ?>" autocomplete="username" required><?= validation_error($errors, 'username') ?></div>
                </div>
                <div class="form-grid two">
                    <div class="field"><label for="email">Administrator email</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required><?= validation_error($errors, 'email') ?></div>
                    <div class="field"><label for="support_email">Support email</label><input id="support_email" name="support_email" type="email" value="<?= old('support_email') ?>" autocomplete="email" required><?= validation_error($errors, 'support_email') ?></div>
                </div>
                <div class="form-grid two">
                    <div class="field"><label for="organisation">School or organisation</label><input id="organisation" name="organisation" value="<?= old('organisation') ?>" autocomplete="organization" required><?= validation_error($errors, 'organisation') ?></div>
                    <div class="field"><label for="platform_name">Platform name</label><input id="platform_name" name="platform_name" value="<?= old('platform_name', 'CodeMwana') ?>" required><?= validation_error($errors, 'platform_name') ?></div>
                </div>
                <div class="form-grid two">
                    <div class="field"><label for="password">Administrator password</label><input id="password" name="password" type="password" autocomplete="new-password" required><?= validation_error($errors, 'password') ?></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><?= validation_error($errors, 'password_confirmation') ?></div>
                </div>
                <button class="button button-large" type="submit">Complete setup<?= icon('arrow-right') ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

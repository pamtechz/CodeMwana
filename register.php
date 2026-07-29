<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) redirect('dashboard.php');
$errors = [];
if (is_post()) {
    verify_csrf();
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'username' => trim((string) ($_POST['username'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'age_group' => (string) ($_POST['age_group'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
    ];
    set_old(array_diff_key($data, ['password' => true, 'password_confirmation' => true]));
    if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 80) $errors['name'] = 'Name must contain 2 to 80 characters.';
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) $errors['username'] = 'Use 3 to 20 letters, numbers or underscores.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!in_array($data['age_group'], ['8-10', '11-13', '14-16'], true)) $errors['age_group'] = 'Choose an age group.';
    if (strlen($data['password']) < 8) $errors['password'] = 'Use at least 8 characters.';
    if ($data['password'] !== $data['password_confirmation']) $errors['password_confirmation'] = 'Passwords do not match.';
    if (!$errors) {
        if (Database::fetch('SELECT id FROM users WHERE email = ? OR username = ?', [strtolower($data['email']), strtolower($data['username'])])) {
            $errors['general'] = 'That email address or username is already registered.';
        } else {
            $userId = Auth::register($data);
            Auth::loginById($userId);
            clear_old();
            flash('success', 'Your learner account has been created. Welcome to CodeMwana!');
            redirect('dashboard.php');
        }
    }
}
$pageTitle = 'Create learner account';
$bodyClass = 'auth-page';
require base_path('partials/header.php');
?>
<section class="auth-section auth-register">
    <div class="auth-side auth-side-register">
        <div class="auth-side-content">
            <span class="eyebrow light">Begin with confidence</span>
            <h1>Create, test and improve ideas one step at a time.</h1>
            <ul class="auth-benefits">
                <li><span>✓</span> Short lessons for beginners</li>
                <li><span>✓</span> Safe interactive code lab</li>
                <li><span>✓</span> Saved progress and projects</li>
                <li><span>✓</span> No public messaging or adverts</li>
            </ul>
        </div>
    </div>
    <div class="auth-main">
        <div class="auth-card auth-card-wide">
            <div class="auth-heading"><h2>Create a learner account</h2><p>Use an email address that can be recovered with help from a parent, guardian or teacher.</p></div>
            <?php if (isset($errors['general'])): ?><div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div><?php endif; ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group"><label for="name">Learner name</label><input id="name" name="name" value="<?= old('name') ?>" maxlength="80" required><?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?></div>
                    <div class="form-group"><label for="username">Username</label><input id="username" name="username" value="<?= old('username') ?>" maxlength="20" autocomplete="username" required><small>Letters, numbers and underscores only.</small><?php if (isset($errors['username'])): ?><small class="field-error"><?= e($errors['username']) ?></small><?php endif; ?></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required><?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?></div>
                    <div class="form-group"><label for="age_group">Age group</label><select id="age_group" name="age_group" required><option value="">Choose one</option><option value="8-10" <?= old('age_group') === '8-10' ? 'selected' : '' ?>>8–10 years</option><option value="11-13" <?= old('age_group') === '11-13' ? 'selected' : '' ?>>11–13 years</option><option value="14-16" <?= old('age_group') === '14-16' ? 'selected' : '' ?>>14–16 years</option></select><?php if (isset($errors['age_group'])): ?><small class="field-error"><?= e($errors['age_group']) ?></small><?php endif; ?></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?></div>
                    <div class="form-group"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required><?php if (isset($errors['password_confirmation'])): ?><small class="field-error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?></div>
                </div>
                <label class="check-row"><input type="checkbox" required><span>I understand this account stores learning progress and should be created with appropriate adult guidance.</span></label>
                <button class="button button-full button-large" type="submit">Create account and start</button>
            </form>
            <p class="auth-switch">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
        </div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

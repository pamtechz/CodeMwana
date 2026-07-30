<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) redirect('dashboard.php');
if (!Database::tableExists('users')) redirect('setup.php');
if ((string) setting('registration_open', '1') !== '1') { flash('error', 'Learner registration is currently closed.'); redirect('login.php'); }

$errors = [];
$publicStats = Learning::publicStatistics();
if (is_post()) {
    verify_csrf();
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
        'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
        'school_name' => trim((string) ($_POST['school_name'] ?? '')),
        'age_group' => (string) ($_POST['age_group'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
    ];
    set_old(array_diff_key($data, ['password' => true, 'password_confirmation' => true]));
    if (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 100) $errors['name'] = 'Enter a name between 3 and 100 characters.';
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $data['username'])) $errors['username'] = 'Use 3–40 lowercase letters, numbers, dots, dashes or underscores.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (mb_strlen($data['school_name']) < 2) $errors['school_name'] = 'Enter your school, learning centre or home-school name.';
    if (!in_array($data['age_group'], ['8-10','11-13','14-16','17+'], true)) $errors['age_group'] = 'Select the correct age group.';
    if (strlen($data['password']) < 10 || !preg_match('/[A-Z]/', $data['password']) || !preg_match('/[a-z]/', $data['password']) || !preg_match('/\d/', $data['password'])) $errors['password'] = 'Use at least 10 characters with uppercase, lowercase and a number.';
    if ($data['password'] !== $data['password_confirmation']) $errors['password_confirmation'] = 'The passwords do not match.';
    if (!$errors && Database::fetch('SELECT id FROM users WHERE LOWER(email) = ? OR LOWER(username) = ?', [$data['email'], $data['username']])) $errors['account'] = 'That email address or username is already connected to an account.';
    if (!$errors) {
        $userId = Auth::register($data);
        Auth::loginById($userId);
        clear_old();
        flash('success', 'Your learner account has been created. Choose a learning path to begin.');
        redirect('courses.php');
    }
}
$pageTitle = 'Create learner account';
$bodyClass = 'auth-page register-page';
require base_path('partials/header.php');
?>
<section class="auth-layout register-layout">
    <div class="auth-visual register-visual">
        <div class="auth-visual-content"><span class="eyebrow light">A workspace that grows with you</span><h1>Create, practise and see your progress clearly.</h1><p>CodeMwana stores learning activity securely so you can move between devices and continue where you stopped.</p><div class="auth-feature-list"><div><?= icon('book-open') ?><span><strong><?= number_format($publicStats['lessons']) ?> complete lessons</strong><small>Algorithms, MwanaCode, turtle graphics and web creation.</small></span></div><div><?= icon('folder-code') ?><span><strong>Personal project library</strong><small>Save programs and keep previous versions.</small></span></div><div><?= icon('shield-check') ?><span><strong>Child-focused safety</strong><small>No public chat, advertising or arbitrary code execution.</small></span></div></div></div>
    </div>
    <div class="auth-panel">
        <div class="auth-card wide">
            <div class="auth-heading"><span class="auth-mark">CM</span><h2>Create learner account</h2><p>All fields below are used for account access, progress records or learning reports.</p></div>
            <?php if (isset($errors['account'])): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($errors['account']) ?></span></div><?php endif; ?>
            <form method="post" class="form-stack" data-progress-form>
                <?= csrf_field() ?>
                <div class="form-grid two">
                    <div class="field"><label for="name">Learner name</label><input id="name" name="name" value="<?= old('name') ?>" autocomplete="name" required><?= validation_error($errors, 'name') ?></div>
                    <div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= old('username') ?>" autocomplete="username" required><?= validation_error($errors, 'username') ?></div>
                </div>
                <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required><?= validation_error($errors, 'email') ?></div>
                <div class="form-grid two">
                    <div class="field"><label for="school_name">School or learning centre</label><input id="school_name" name="school_name" value="<?= old('school_name') ?>" autocomplete="organization" required><?= validation_error($errors, 'school_name') ?></div>
                    <div class="field"><label for="age_group">Age group</label><select id="age_group" name="age_group" required><option value="">Select age group</option><?php foreach (['8-10'=>'8–10 years','11-13'=>'11–13 years','14-16'=>'14–16 years','17+'=>'17 years or older'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= old('age_group') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?= validation_error($errors, 'age_group') ?></div>
                </div>
                <div class="form-grid two">
                    <div class="field"><label for="password">Password</label><div class="password-field"><input id="password" name="password" type="password" autocomplete="new-password" required data-password-strength><button type="button" data-password-toggle="password">Show</button></div><div class="password-meter" data-password-meter><span></span></div><?= validation_error($errors, 'password') ?></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><div class="password-field"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Show</button></div><?= validation_error($errors, 'password_confirmation') ?></div>
                </div>
                <label class="consent-check"><input type="checkbox" name="terms" required><span>I will use CodeMwana for learning and understand that a parent, guardian or teacher should guide younger learners.</span></label>
                <button class="button button-large button-full" type="submit" data-submit-button><span>Create my account</span><?= icon('arrow-right') ?></button>
            </form>
            <p class="auth-switch">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
        </div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

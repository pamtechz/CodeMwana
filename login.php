<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) redirect('dashboard.php');

$errors = [];
if (is_post()) {
    verify_csrf();
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    set_old(['identifier' => $identifier]);
    if ($identifier === '') $errors['identifier'] = 'Enter your email address or username.';
    if ($password === '') $errors['password'] = 'Enter your password.';
    if (!$errors) {
        $result = Auth::attempt($identifier, $password);
        if ($result['ok']) {
            clear_old();
            flash('success', 'Welcome back. Your learning workspace is ready.');
            redirect(intended_url('dashboard.php'));
        }
        $errors['login'] = $result['message'];
    }
}
$pageTitle = 'Sign in';
$bodyClass = 'auth-page';
require base_path('partials/header.php');
?>
<section class="auth-layout">
    <div class="auth-visual">
        <div class="auth-visual-content">
            <span class="eyebrow light">Continue your learning journey</span>
            <h1>Return to your lessons, projects and progress.</h1>
            <p>Every completed lesson, quiz score and saved program remains connected to your account.</p>
            <div class="auth-feature-list">
                <div><?= icon('chart') ?><span><strong>Persistent progress</strong><small>Continue from the exact lesson you last used.</small></span></div>
                <div><?= icon('terminal') ?><span><strong>Safe Code Lab</strong><small>Build and save MwanaCode programs.</small></span></div>
                <div><?= icon('trophy') ?><span><strong>Achievements</strong><small>Earn points and badges through completed work.</small></span></div>
            </div>
        </div>
        <div class="auth-code-card"><div class="code-card-bar"><span></span><span></span><span></span><small>first-program.mwana</small></div><pre><code><b>SET</b> learner = <em>"Mwamba"</em>
<b>SET</b> goal = <em>"Build useful ideas"</em>
<b>SAY</b> learner
<b>SAY</b> goal</code></pre></div>
    </div>
    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-heading"><span class="auth-mark">CM</span><h2>Sign in to CodeMwana</h2><p>Use the account created by you, your teacher or the platform administrator.</p></div>
            <?php if (isset($errors['login'])): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($errors['login']) ?></span></div><?php endif; ?>
            <form method="post" class="form-stack" data-progress-form>
                <?= csrf_field() ?>
                <div class="field"><label for="identifier">Email address or username</label><div class="input-with-icon"><?= icon('user') ?><input id="identifier" name="identifier" value="<?= old('identifier') ?>" autocomplete="username" autofocus required></div><?= validation_error($errors, 'identifier') ?></div>
                <div class="field"><div class="label-row"><label for="password">Password</label><a href="<?= e(url('help.php#account-access')) ?>">Account access help</a></div><div class="password-field input-with-icon"><?= icon('lock') ?><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" data-password-toggle="password">Show</button></div><?= validation_error($errors, 'password') ?></div>
                <button class="button button-large button-full" type="submit" data-submit-button><span>Sign in securely</span><?= icon('arrow-right') ?></button>
            </form>
            <?php if ((string) setting('registration_open', '1') === '1'): ?><p class="auth-switch">New learner? <a href="<?= e(url('register.php')) ?>">Create a learner account</a></p><?php endif; ?>
        </div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

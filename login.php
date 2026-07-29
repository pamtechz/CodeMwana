<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) redirect('dashboard.php');
$errors = [];
if (is_post()) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    set_old(['email' => $email]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($password === '') $errors['password'] = 'Enter your password.';
    if (!$errors && Auth::attempt($email, $password)) {
        clear_old();
        flash('success', 'Welcome back! Your learning journey is ready.');
        redirect('dashboard.php');
    }
    if (!$errors) $errors['general'] = 'The email or password is incorrect.';
}
$pageTitle = 'Sign in';
$bodyClass = 'auth-page';
require base_path('partials/header.php');
?>
<section class="auth-section">
    <div class="auth-side">
        <div class="auth-side-content">
            <span class="eyebrow light">Continue your journey</span>
            <h1>Every program begins with one clear instruction.</h1>
            <p>Sign in to continue lessons, improve saved projects and collect the next achievement badge.</p>
            <div class="quote-card"><span aria-hidden="true">“</span><p>I used a loop so I did not have to write the same command four times.</p><small>Example learner reflection</small></div>
        </div>
    </div>
    <div class="auth-main">
        <div class="auth-card">
            <div class="auth-heading"><h2>Welcome back</h2><p>Use your CodeMwana learner, teacher or administrator account.</p></div>
            <?php if (isset($errors['general'])): ?><div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div><?php endif; ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="<?= old('email') ?>" required aria-describedby="email-error">
                    <?php if (isset($errors['email'])): ?><small class="field-error" id="email-error"><?= e($errors['email']) ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <div class="label-row"><label for="password">Password</label><button class="text-button" type="button" data-password-toggle="password">Show password</button></div>
                    <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error">
                    <?php if (isset($errors['password'])): ?><small class="field-error" id="password-error"><?= e($errors['password']) ?></small><?php endif; ?>
                </div>
                <button class="button button-full button-large" type="submit">Sign in</button>
            </form>
            <p class="auth-switch">New to CodeMwana? <a href="<?= e(url('register.php')) ?>">Create a learner account</a></p>
            <div class="demo-box"><strong>Demonstration accounts after setup</strong><small>Learner: learner@codemwana.test / Learn@123</small><small>Teacher: teacher@codemwana.test / Teacher@123</small></div>
        </div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$errors = [];
if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'profile');
    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $ageGroup = (string) ($_POST['age_group'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors['name'] = 'Name must contain 2 to 80 characters.';
        if (!in_array($ageGroup, ['8-10','11-13','14-16'], true)) $errors['age_group'] = 'Choose a valid age group.';
        if (!$errors) { Database::query('UPDATE users SET name = ?, age_group = ? WHERE id = ?', [$name, $ageGroup, $user['id']]); flash('success', 'Profile updated.'); redirect('profile.php'); }
    }
    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? ''); $password = (string) ($_POST['password'] ?? ''); $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $record = Database::fetch('SELECT password FROM users WHERE id = ?', [$user['id']]);
        if (!$record || !password_verify($current, $record['password'])) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($password) < 8) $errors['password'] = 'Use at least 8 characters.';
        if ($password !== $confirmation) $errors['password_confirmation'] = 'Passwords do not match.';
        if (!$errors) { Database::query('UPDATE users SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]); flash('success', 'Password changed.'); redirect('profile.php'); }
    }
}
$pageTitle = 'Profile'; $bodyClass = 'app-page'; require base_path('partials/header.php');
?>
<section class="page-hero profile-hero"><div class="container"><span class="eyebrow light">Account settings</span><h1>Profile and security</h1><p>Keep the minimum information needed for a useful and recoverable learning account.</p></div></section>
<section class="section"><div class="container settings-layout"><aside class="settings-sidebar"><div class="profile-summary"><span class="avatar extra-large"><?= e(strtoupper(substr($user['name'],0,1))) ?></span><h2><?= e($user['name']) ?></h2><p>@<?= e($user['username']) ?></p><span class="role-pill"><?= e(ucfirst($user['role'])) ?></span></div><nav><a class="active" href="#profile">Profile</a><a href="#password">Password</a><a href="<?= e(url('progress.php')) ?>">Learning record</a></nav></aside><div class="settings-main">
    <section class="settings-card" id="profile"><div class="settings-heading"><h2>Profile information</h2><p>Your email and username are fixed identifiers in this demonstration project.</p></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><div class="form-group"><label for="name">Display name</label><input id="name" name="name" value="<?= e($user['name']) ?>" required><?php if(isset($errors['name'])):?><small class="field-error"><?=e($errors['name'])?></small><?php endif;?></div><div class="form-grid"><div class="form-group"><label>Username</label><input value="<?= e($user['username']) ?>" disabled></div><div class="form-group"><label>Email</label><input value="<?= e($user['email']) ?>" disabled></div></div><div class="form-group"><label for="age_group">Age group</label><select id="age_group" name="age_group"><?php foreach(['8-10','11-13','14-16'] as $group):?><option value="<?=$group?>" <?= $user['age_group']===$group?'selected':'' ?>><?=$group?> years</option><?php endforeach;?></select><?php if(isset($errors['age_group'])):?><small class="field-error"><?=e($errors['age_group'])?></small><?php endif;?></div><button class="button" type="submit">Save profile</button></form></section>
    <section class="settings-card" id="password"><div class="settings-heading"><h2>Change password</h2><p>Use at least eight characters and avoid reusing a password from another account.</p></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password"><div class="form-group"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required><?php if(isset($errors['current_password'])):?><small class="field-error"><?=e($errors['current_password'])?></small><?php endif;?></div><div class="form-grid"><div class="form-group"><label for="password">New password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><?php if(isset($errors['password'])):?><small class="field-error"><?=e($errors['password'])?></small><?php endif;?></div><div class="form-group"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required><?php if(isset($errors['password_confirmation'])):?><small class="field-error"><?=e($errors['password_confirmation'])?></small><?php endif;?></div></div><button class="button" type="submit">Change password</button></form></section>
</div></div></section>
<?php require base_path('partials/footer.php'); ?>

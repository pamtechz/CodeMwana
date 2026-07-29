<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? '';
$bodyClass = $bodyClass ?? '';
$user = current_user();
$current = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6546d7">
    <meta name="description" content="CodeMwana is a child-friendly web application for learning basic programming skills and concepts.">
    <title><?= e(page_title($pageTitle)) ?></title>
    <link rel="manifest" href="<?= e(url('manifest.json')) ?>">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="CodeMwana home">
            <span class="brand-mark" aria-hidden="true">&lt;/&gt;</span>
            <span><strong>CodeMwana</strong><small>Learn. Build. Shine.</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <span class="sr-only">Open navigation</span>
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" id="main-nav" aria-label="Main navigation" data-nav>
            <?php if ($user): ?>
                <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">Dashboard</a>
                <a class="<?= $current === 'courses.php' || $current === 'lesson.php' ? 'active' : '' ?>" href="<?= e(url('courses.php')) ?>">Lessons</a>
                <a class="<?= $current === 'playground.php' ? 'active' : '' ?>" href="<?= e(url('playground.php')) ?>">Code Lab</a>
                <a class="<?= $current === 'progress.php' ? 'active' : '' ?>" href="<?= e(url('progress.php')) ?>">Progress</a>
                <a class="<?= $current === 'leaderboard.php' ? 'active' : '' ?>" href="<?= e(url('leaderboard.php')) ?>">Leaderboard</a>
                <?php if (in_array($user['role'], ['teacher', 'admin'], true)): ?>
                    <a href="<?= e(url('teacher/dashboard.php')) ?>">Teacher</a>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= e(url('admin/dashboard.php')) ?>">Admin</a>
                <?php endif; ?>
                <div class="user-menu">
                    <a class="user-chip" href="<?= e(url('profile.php')) ?>">
                        <span class="avatar" aria-hidden="true"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
                        <span><?= e($user['name']) ?></span>
                    </a>
                    <a class="button button-small button-ghost" href="<?= e(url('logout.php')) ?>">Sign out</a>
                </div>
            <?php else: ?>
                <a href="<?= e(url('index.php#features')) ?>">Features</a>
                <a href="<?= e(url('index.php#how-it-works')) ?>">How it works</a>
                <a href="<?= e(url('index.php#safety')) ?>">Safety</a>
                <a class="button button-small button-ghost" href="<?= e(url('login.php')) ?>">Sign in</a>
                <a class="button button-small" href="<?= e(url('register.php')) ?>">Start learning</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if ($message = flash('success')): ?>
    <div class="toast toast-success" role="status" data-toast><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="toast toast-error" role="alert" data-toast><?= e($message) ?></div>
<?php endif; ?>
<main id="main-content">

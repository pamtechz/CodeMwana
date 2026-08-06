<?php
$pageTitle = $pageTitle ?? '';
$bodyClass = $bodyClass ?? '';
$pageDescription = $pageDescription ?? setting('site_description', 'Programming learning platform for children.');
$pageStyles = $pageStyles ?? [];
$user = current_user();
$siteName = (string) setting('site_name', 'CodeMwana');
$tagline = (string) setting('site_tagline', 'Learn. Build. Shine.');
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$isRootDashboard = basename($currentPath) === 'dashboard.php' && !str_contains($currentPath, '/teacher/') && !str_contains($currentPath, '/admin/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#5B4BDB">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e(page_title($pageTitle)) ?></title>
    <link rel="manifest" href="<?= e(url('manifest.json')) ?>">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app-v3.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app-v4.css')) ?>">
    <?php foreach ((array) $pageStyles as $style): ?><link rel="stylesheet" href="<?= e(asset('css/' . ltrim((string) $style, '/'))) ?>"><?php endforeach; ?>
</head>
<body class="<?= e(trim(($user ? 'authenticated ' : 'public ') . $bodyClass)) ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php if ($user): ?>
<div class="app-shell" data-app-shell>
    <aside class="app-sidebar" aria-label="Application navigation" data-sidebar>
        <div class="sidebar-head">
            <a class="brand" href="<?= e(url('dashboard.php')) ?>">
                <span class="brand-mark">CM</span>
                <span><strong><?= e($siteName) ?></strong><small><?= e($tagline) ?></small></span>
            </a>
            <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Close navigation"><?= icon('x') ?></button>
        </div>
        <nav class="sidebar-nav">
            <span class="nav-label">Workspace</span>
            <a class="<?= $isRootDashboard ? 'is-active' : '' ?>" href="<?= e(url('dashboard.php')) ?>"><?= icon('home') ?><span>Overview</span></a>
            <a class="<?= active_nav(['courses.php','course.php','lesson.php','quiz.php']) ?>" href="<?= e(url('courses.php')) ?>"><?= icon('book-open') ?><span>Learning paths</span></a>
            <a class="<?= active_nav('playground.php') ?>" href="<?= e(url('playground.php')) ?>"><?= icon('terminal') ?><span>Code Lab</span></a>
            <a class="<?= active_nav('projects.php') ?>" href="<?= e(url('projects.php')) ?>"><?= icon('folder-code') ?><span>My projects</span></a>
            <a class="<?= active_nav('progress.php') ?>" href="<?= e(url('progress.php')) ?>"><?= icon('chart') ?><span>Progress</span></a>
            <?php if ((string) setting('leaderboard_enabled', '1') === '1'): ?><a class="<?= active_nav('leaderboard.php') ?>" href="<?= e(url('leaderboard.php')) ?>"><?= icon('trophy') ?><span>Leaderboard</span></a><?php endif; ?>
            <?php if (in_array($user['role'], ['teacher','admin'], true)): ?>
                <span class="nav-label">Teaching</span>
                <a class="<?= str_contains($currentPath, '/teacher/') ? 'is-active' : '' ?>" href="<?= e(url('teacher/dashboard.php')) ?>"><?= icon('users') ?><span>Teacher centre</span></a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <span class="nav-label">Administration</span>
                <a class="<?= str_contains($currentPath, '/admin/') ? 'is-active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('settings') ?><span>Platform admin</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <a href="<?= e(url('profile.php')) ?>" class="sidebar-user-card">
                <span class="avatar"><?= e(initials($user['name'])) ?></span>
                <span><strong><?= e($user['name']) ?></strong><small><?= e(ucfirst($user['role'])) ?></small></span>
                <?= icon('arrow-right') ?>
            </a>
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="sidebar-signout" type="submit"><?= icon('log-out') ?><span>Sign out</span></button>
            </form>
        </div>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="app-main">
        <header class="app-topbar">
            <button class="icon-button mobile-menu" type="button" data-sidebar-open aria-label="Open navigation"><?= icon('menu') ?></button>
            <div class="topbar-context"><span><?= e($pageTitle ?: 'Workspace') ?></span><small><?= e(date('l, j F Y')) ?></small></div>
            <div class="topbar-actions">
                <a class="icon-button" href="<?= e(url('dashboard.php#announcements')) ?>" aria-label="Announcements"><?= icon('bell') ?></a>
                <a class="topbar-profile" href="<?= e(url('profile.php')) ?>"><span class="avatar small"><?= e(initials($user['name'])) ?></span><span><strong><?= e($user['name']) ?></strong><small><?= e($user['school_name'] ?: ucfirst($user['role'])) ?></small></span></a>
            </div>
        </header>
<?php else: ?>
<header class="public-header" data-header>
    <div class="container public-header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark">CM</span>
            <span><strong><?= e($siteName) ?></strong><small><?= e($tagline) ?></small></span>
        </a>
        <button class="icon-button public-menu" type="button" data-nav-toggle aria-expanded="false" aria-controls="public-nav" aria-label="Open navigation"><?= icon('menu') ?></button>
        <nav class="public-nav" id="public-nav" data-nav>
            <a href="<?= e(url('index.php#learning')) ?>">Learning paths</a>
            <a href="<?= e(url('index.php#experience')) ?>">Experience</a>
            <a href="<?= e(url('about.php')) ?>">About</a>
            <a href="<?= e(url('help.php')) ?>">Help</a>
            <a class="button button-secondary button-small" href="<?= e(url('login.php')) ?>">Sign in</a>
            <?php if ((string) setting('registration_open', '1') === '1'): ?><a class="button button-small" href="<?= e(url('register.php')) ?>">Create account</a><?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>
<?php if ($message = flash('success')): ?><div class="toast toast-success" role="status" data-toast><?= icon('check-circle') ?><span><?= e($message) ?></span></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="toast toast-error" role="alert" data-toast><?= icon('alert-circle') ?><span><?= e($message) ?></span></div><?php endif; ?>
<main id="main-content" class="<?= $user ? 'workspace-content' : 'public-content' ?>">
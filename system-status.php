<?php
require_once __DIR__ . '/app/bootstrap.php';

$state = Installation::state(true);
$checks = [
    ['PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=')],
    ['Database connection', $state['database_error'] ? 'Unavailable' : 'Connected', !$state['database_error']],
    ['Installation state', $state['installed'] ? 'Installed' : 'Not complete', $state['installed']],
    ['Installation lock', $state['lock_exists'] ? 'Present' : 'Not present', $state['lock_exists'] || $state['installed']],
    ['Setup file', $state['setup_exists'] ? 'Present' : 'Removed', $state['installed'] || $state['setup_exists']],
    ['Schema version', $state['schema_version'] ?: 'Not detected', $state['installed']],
    ['Browser runtimes', 'Python and PHP available without an API key', true],
    ['External compiler', CodeRunner::configured() ? 'Configured for C, C++ and Go' : 'Optional—C, C++ and Go cannot run', true],
];
$pageTitle = 'System status';
$bodyClass = 'system-status-page';
require base_path('partials/header.php');
?>
<section class="section compact-section">
    <div class="container narrow">
        <div class="page-intro compact-intro"><div><span class="eyebrow">Operational diagnostics</span><h1>CodeMwana system status</h1><p>This page checks installation, database and Code Lab readiness without sending the application back to setup.php.</p></div></div>
        <section class="panel status-check-list">
            <?php foreach ($checks as [$label, $value, $ok]): ?>
                <div class="status-check-row"><span class="status-check-icon <?= $ok ? 'success' : 'danger' ?>"><?= icon($ok ? 'check-circle' : 'alert-circle') ?></span><div><strong><?= e($label) ?></strong><small><?= e($value) ?></small></div></div>
            <?php endforeach; ?>
        </section>
        <?php if ($state['database_error']): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span>The configured database could not be reached. Confirm the database service and .env credentials. The platform will not redirect to setup while an installation lock exists.</span></div><?php endif; ?>
        <div class="status-actions"><a class="button" href="<?= e(url('index.php')) ?>"><?= icon('home') ?>Return to platform</a><?php if (!$state['installed'] && $state['setup_exists']): ?><a class="button button-secondary" href="<?= e(url('setup.php')) ?>">Open installer</a><?php endif; ?></div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

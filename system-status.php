<?php
require_once __DIR__ . '/app/bootstrap.php';
require_role('admin');

$state = Installation::state(true);
$checks = [
    ['Application runtime', version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Ready' : 'Update required', version_compare(PHP_VERSION, '8.1.0', '>=')],
    ['Data service', $state['database_error'] ? 'Unavailable' : 'Connected', !$state['database_error']],
    ['Installation state', $state['installed'] ? 'Ready' : 'Incomplete', $state['installed']],
    ['Application lock', $state['lock_exists'] ? 'Present' : 'Not present', $state['lock_exists'] || $state['installed']],
    ['Schema state', $state['schema_version'] ? 'Detected' : 'Not detected', $state['installed']],
    ['Browser learning tools', 'Ready', true],
    ['Managed execution', CodeRunner::configured() ? 'Primary service ready' : 'Alternate workspace active', true],
    ['Alternate workspace', CodeRunner::fallbackAvailable() ? 'Ready' : 'Not configured', CodeRunner::fallbackAvailable()],
];
$pageTitle = 'System status';
$bodyClass = 'system-status-page';
require base_path('partials/header.php');
?>
<section class="section compact-section">
    <div class="container narrow">
        <div class="page-intro compact-intro"><div><span class="eyebrow">Restricted diagnostics</span><h1>System status</h1><p>Operational checks are visible only to authorised administrators.</p></div></div>
        <section class="panel status-check-list">
            <?php foreach ($checks as [$label, $value, $ok]): ?>
                <div class="status-check-row"><span class="status-check-icon <?= $ok ? 'success' : 'danger' ?>"><?= icon($ok ? 'check-circle' : 'alert-circle') ?></span><div><strong><?= e($label) ?></strong><small><?= e($value) ?></small></div></div>
            <?php endforeach; ?>
        </section>
        <?php if ($state['database_error']): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span>A required service is unavailable. Review the private server logs and hosting control panel.</span></div><?php endif; ?>
        <div class="status-actions"><a class="button" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('arrow-left') ?>Return to administration</a></div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();

if (is_post()) {
    verify_csrf();
    if ((string) ($_POST['action'] ?? '') === 'delete') {
        $id = request_int('project_id');
        if (Learning::deleteProject($id, (int) $user['id'])) flash('success', 'The project and its saved versions were removed.');
        else flash('error', 'The project could not be found.');
        redirect('projects.php');
    }
}

$projects = Learning::projects((int) $user['id']);
$languages = Learning::languages(true);
$languageMap = [];
foreach ($languages as $language) $languageMap[$language['slug']] = $language;
$counts = [];
foreach ($projects as $project) $counts[$project['language']] = ($counts[$project['language']] ?? 0) + 1;

$pageTitle = 'My projects';
$bodyClass = 'projects-page';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro">
    <div><span class="eyebrow">Multi-language project library</span><h1>My projects</h1><p>Create, run and version projects across the CodeMwana language catalogue. Every project remains connected to your account.</p></div>
    <div class="page-intro-actions"><a class="button button-large" href="<?= e(url('playground.php')) ?>"><?= icon('plus') ?>New Code Lab project</a></div>
</section>
<section class="language-launcher panel">
    <div class="panel-heading"><div><span class="eyebrow">Start from a real workspace</span><h2>Choose a language</h2><p>Each language opens with database-managed starter files and the correct preview or execution mode.</p></div><span class="count-badge"><?= count($languages) ?> modes</span></div>
    <div class="language-launch-grid">
        <?php foreach ($languages as $language): ?>
            <a href="<?= e(url('playground.php?language=' . urlencode($language['slug']))) ?>" style="--language:<?= e($language['colour']) ?>">
                <span><?= e($language['short_name']) ?></span><div><strong><?= e($language['name']) ?></strong><small><?= e($language['category']) ?> · <?= e($language['execution_mode'] === 'remote' ? 'sandbox runner' : 'browser runtime') ?></small></div><?= icon('arrow-right') ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php if ($projects): ?>
    <div class="project-toolbar">
        <div class="search-field"><?= icon('search') ?><label class="sr-only" for="project-search">Search projects</label><input id="project-search" aria-label="Search projects" data-filter-input data-filter-target=".project-card"></div>
        <div class="project-filter-summary"><span><?= count($projects) ?> saved <?= count($projects) === 1 ? 'project' : 'projects' ?></span><?php foreach ($counts as $slug => $count): ?><small><?= e($languageMap[$slug]['short_name'] ?? strtoupper($slug)) ?> <?= (int) $count ?></small><?php endforeach; ?></div>
    </div>
    <section class="project-grid">
        <?php foreach ($projects as $project): $language = $project['language_meta']; $previewFile = $language['main_file'] ?? array_key_first($project['workspace']); $previewCode = (string) ($project['workspace'][$previewFile] ?? $project['code']); ?>
            <article class="project-card" data-filter-item style="--language:<?= e($language['colour']) ?>">
                <div class="project-card-head"><span class="project-language"><i><?= e($language['short_name']) ?></i><?= e($language['name']) ?></span><div class="dropdown" data-dropdown><button class="icon-button" type="button" data-dropdown-toggle aria-label="Project actions"><?= icon('more-horizontal') ?></button><div class="dropdown-menu"><a href="<?= e(url('playground.php?project=' . (int) $project['id'])) ?>"><?= icon('edit') ?>Edit project</a><form method="post" data-confirm="Delete this project and all saved versions?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>"><button type="submit"><?= icon('trash') ?>Delete project</button></form></div></div></div>
                <div class="project-code-preview"><div class="project-file-label"><?= e($previewFile) ?></div><pre><code><?= e(implode("\n", array_slice(explode("\n", $previewCode), 0, 8))) ?></code></pre></div>
                <div class="project-card-body"><h2><?= e($project['title']) ?></h2><div class="inline-meta"><span><?= icon('clock') ?>Updated <?= e(time_ago($project['updated_at'])) ?></span><span><?= icon('folder-code') ?><?= count($project['workspace']) ?> files</span><span><?= icon('save') ?><?= (int) $project['version_count'] ?> versions</span></div></div>
                <a class="project-open" href="<?= e(url('playground.php?project=' . (int) $project['id'])) ?>">Open in Code Lab<?= icon('arrow-right') ?></a>
            </article>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="panel empty-panel large"><span class="empty-icon"><?= icon('folder-code') ?></span><h2>Your project library is ready</h2><p>Select a programming language above. CodeMwana will create the correct files and keep later changes in version history.</p><a class="button button-large" href="<?= e(url('playground.php')) ?>"><?= icon('plus') ?>Create first project</a></section>
<?php endif; ?>
<?php require base_path('partials/footer.php'); ?>

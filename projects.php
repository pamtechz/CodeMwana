<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
if (is_post()) {
    verify_csrf();
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    if ($projectId) {
        Database::query('DELETE FROM projects WHERE id = ? AND user_id = ?', [$projectId, $user['id']]);
        flash('success', 'Project deleted.');
    }
    redirect('projects.php');
}
$projects = Learning::projects((int) $user['id']);
$pageTitle = 'My projects';
$bodyClass = 'app-page';
require base_path('partials/header.php');
?>
<section class="page-hero projects-hero"><div class="container page-hero-row"><div><span class="eyebrow light">Creative coding</span><h1>My saved projects</h1><p>Return to earlier programs, improve them and observe how your ideas develop.</p></div><a class="button button-light button-large" href="<?= e(url('playground.php')) ?>">+ New project</a></div></section>
<section class="section"><div class="container">
    <?php if (!$projects): ?><div class="empty-state"><span aria-hidden="true">💻</span><h2>Your project shelf is empty</h2><p>Create a MwanaCode program and save it so that you can continue during another session.</p><a class="button" href="<?= e(url('playground.php')) ?>">Open Code Lab</a></div><?php else: ?>
    <div class="project-grid">
        <?php foreach ($projects as $project): ?>
        <article class="project-card"><div class="project-preview"><span class="project-language">MwanaCode</span><pre><code><?= e(mb_strimwidth($project['code'], 0, 260, "\n…")) ?></code></pre></div><div class="project-card-body"><h2><?= e($project['title']) ?></h2><p>Updated <?= e(time_ago($project['updated_at'])) ?></p><div class="project-actions"><a class="button button-small" href="<?= e(url('playground.php?project=' . (int) $project['id'])) ?>">Open project</a><form method="post" data-confirm="Delete this project? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>"><button class="button button-small button-danger-ghost" type="submit">Delete</button></form></div></div></article>
        <?php endforeach; ?>
    </div><?php endif; ?>
</div></section>
<?php require base_path('partials/footer.php'); ?>

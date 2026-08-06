<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

if (is_post()) {
    verify_csrf();
    $pageId = request_int('page_id');
    $page = Database::fetch('SELECT id, slug, is_published FROM public_pages WHERE id = ?', [$pageId]);
    if (!$page) {
        flash('error', 'The selected public page could not be found.');
        redirect('admin/public-pages.php');
    }

    $published = (int) $page['is_published'] === 1 ? 0 : 1;
    Database::query(
        'UPDATE public_pages SET is_published = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
        [$published, (int) current_user()['id'], $pageId]
    );
    PublicPages::reset();
    activity('public_page_publication_changed', ['page_id' => $pageId, 'slug' => $page['slug'], 'is_published' => $published]);
    flash('success', $published ? 'The public page is now published.' : 'The public page is now hidden from visitors.');
    redirect('admin/public-pages.php');
}

$pages = PublicPages::all(false);
$publishedCount = count(array_filter($pages, static fn (array $page): bool => (int) $page['is_published'] === 1));
$headerCount = count(array_filter($pages, static fn (array $page): bool => (int) $page['show_in_header'] === 1));
$footerCount = count(array_filter($pages, static fn (array $page): bool => (int) $page['show_in_footer'] === 1));
$pageTitle = 'Public pages';
$bodyClass = 'admin-public-pages-page';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro">
    <div>
        <a class="back-link" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('arrow-left') ?>Administration</a>
        <span class="eyebrow">Public content</span>
        <h1>Public pages</h1>
        <p>Edit the information visitors use to understand the platform, contact the organisation and get help.</p>
    </div>
    <div class="page-intro-actions"><a class="button button-secondary" href="<?= e(url('index.php')) ?>">View public site</a></div>
</section>

<section class="metric-grid four">
    <article class="metric-card"><span class="metric-icon purple"><?= icon('book-open') ?></span><div><small>Managed pages</small><strong><?= count($pages) ?></strong></div></article>
    <article class="metric-card"><span class="metric-icon green"><?= icon('check-circle') ?></span><div><small>Published</small><strong><?= $publishedCount ?></strong></div></article>
    <article class="metric-card"><span class="metric-icon blue"><?= icon('menu') ?></span><div><small>Header links</small><strong><?= $headerCount ?></strong></div></article>
    <article class="metric-card"><span class="metric-icon orange"><?= icon('route') ?></span><div><small>Footer links</small><strong><?= $footerCount ?></strong></div></article>
</section>

<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">Page inventory</span><h2>Information pages</h2><p>Page URLs remain fixed while headings, content, actions and navigation visibility can be changed.</p></div>
    </div>
    <div class="curriculum-record-list public-page-record-list">
        <?php foreach ($pages as $page): ?>
            <article class="curriculum-record-card">
                <div class="curriculum-record-main">
                    <span class="curriculum-record-icon"><?= icon(match ((string) $page['slug']) { 'contact' => 'mail', 'privacy' => 'shield-check', 'help' => 'info', 'developers' => 'users', default => 'book-open' }) ?></span>
                    <div>
                        <div class="curriculum-record-title"><h3><?= e((string) $page['title']) ?></h3><span class="status-badge <?= (int) $page['is_published'] ? 'success' : 'warning' ?>"><?= (int) $page['is_published'] ? 'Published' : 'Hidden' ?></span></div>
                        <p><?= e((string) $page['hero_text']) ?></p>
                        <div class="curriculum-record-meta">
                            <span><?= e((string) $page['navigation_label']) ?></span>
                            <span><?= (int) $page['show_in_header'] ? 'Header' : 'No header link' ?></span>
                            <span><?= (int) $page['show_in_footer'] ? 'Footer' : 'No footer link' ?></span>
                            <span>Order <?= (int) $page['sort_order'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="curriculum-record-actions">
                    <a class="button button-small" href="<?= e(url('admin/public-page-edit.php?id=' . (int) $page['id'])) ?>"><?= icon('edit') ?>Edit</a>
                    <a class="button button-secondary button-small" href="<?= e(PublicPages::urlFor((string) $page['slug']) . ((int) $page['is_published'] ? '' : '?preview=1')) ?>">Preview</a>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="page_id" value="<?= (int) $page['id'] ?>">
                        <button class="button button-secondary button-small" type="submit"><?= (int) $page['is_published'] ? 'Hide' : 'Publish' ?></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

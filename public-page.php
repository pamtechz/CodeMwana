<?php
require_once __DIR__ . '/app/bootstrap.php';

$slug = strtolower(trim((string) ($publicPageSlug ?? $_GET['page'] ?? '')));
$routes = PublicPages::routeMap();
if (!array_key_exists($slug, $routes)) {
    http_response_code(404);
    $pageTitle = 'Page not found';
    $pageDescription = 'The requested page could not be found.';
    require base_path('partials/header.php');
    echo '<section class="state-page"><div class="state-card"><span class="state-icon">404</span><h1>Page not found</h1><p>The requested information page is not available.</p><a class="button" href="' . e(url('index.php')) . '">Return home</a></div></section>';
    require base_path('partials/footer.php');
    exit;
}

$user = Auth::check() ? current_user() : null;
$preview = $user && ($user['role'] ?? '') === 'admin' && (string) ($_GET['preview'] ?? '') === '1';
$page = PublicPages::find($slug, !$preview);
if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page not available';
    $pageDescription = 'The requested page is not currently available.';
    require base_path('partials/header.php');
    echo '<section class="state-page"><div class="state-card"><span class="state-icon">404</span><h1>Page not available</h1><p>This information page is not currently published.</p><a class="button" href="' . e(url('index.php')) . '">Return home</a></div></section>';
    require base_path('partials/footer.php');
    exit;
}

$pageTitle = PublicPages::resolveText((string) $page['title']);
$pageDescription = PublicPages::resolveText((string) $page['meta_description']);
$bodyClass = 'content-page managed-public-page';
$pageStyles = ['public-pages.css'];
$heroTitle = PublicPages::resolveText((string) $page['hero_title']);
$heroText = PublicPages::resolveText((string) $page['hero_text']);
$eyebrow = PublicPages::resolveText((string) $page['eyebrow']);
$content = PublicPages::resolveHtml((string) $page['content_html']);
$ctaLabel = PublicPages::resolveText((string) ($page['cta_label'] ?? ''));
$rawCtaUrl = trim((string) ($page['cta_url'] ?? ''));
$missingSupportContact = str_contains($rawCtaUrl, '{{support_email}}') && trim((string) setting('support_email', '')) === '';
$ctaUrl = $missingSupportContact ? '' : PublicPages::resolveUrl($rawCtaUrl);
$related = array_values(array_filter(
    PublicPages::all(true),
    static fn (array $item): bool => (string) $item['slug'] !== $slug
));

require base_path('partials/header.php');
?>
<?php if ($preview): ?><div class="alert alert-warning public-preview-banner" role="status"><?= icon('info') ?><span>Administrator preview. This page may not be visible to public visitors.</span></div><?php endif; ?>
<section class="content-hero">
    <div class="container content-hero-grid">
        <div>
            <?php if ($eyebrow !== ''): ?><span class="eyebrow"><?= e($eyebrow) ?></span><?php endif; ?>
            <h1><?= e($heroTitle) ?></h1>
            <p><?= e($heroText) ?></p>
            <?php if ($ctaLabel !== '' && $ctaUrl !== ''): ?><div class="hero-actions"><a class="button" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?><?= icon('arrow-right') ?></a></div><?php endif; ?>
        </div>
        <aside class="content-hero-stats" aria-label="Page information">
            <div><strong><?= e((string) setting('site_name', 'CodeMwana')) ?></strong><span>Learning platform</span></div>
            <div><strong><?= e(date('Y')) ?></strong><span>Current information</span></div>
            <div><strong><?= (int) count($related) + 1 ?></strong><span>Information pages</span></div>
        </aside>
    </div>
</section>

<section class="section">
    <div class="container prose-shell">
        <article class="panel prose-panel managed-page-content">
            <?= $content ?>
        </article>
        <aside class="content-side">
            <section class="panel">
                <span class="eyebrow">More information</span>
                <h2>Explore the platform</h2>
                <nav class="managed-page-links" aria-label="Related information pages">
                    <?php foreach (array_slice($related, 0, 6) as $item): ?>
                        <a href="<?= e(PublicPages::urlFor((string) $item['slug'])) ?>"><span><?= e((string) $item['navigation_label']) ?></span><?= icon('arrow-right') ?></a>
                    <?php endforeach; ?>
                </nav>
            </section>
            <?php if ($preview): ?><section class="panel"><span class="eyebrow">Administration</span><h2>Edit this page</h2><p>Return to public-page management to change content, navigation and publishing settings.</p><a class="button button-secondary" href="<?= e(url('admin/public-page-edit.php?id=' . (int) $page['id'])) ?>">Open editor</a></section><?php endif; ?>
        </aside>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
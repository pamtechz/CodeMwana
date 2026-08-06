<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

$id = request_int('id');
$page = $id > 0 ? Database::fetch('SELECT * FROM public_pages WHERE id = ?', [$id]) : null;
if (!$page) {
    flash('error', 'The selected public page could not be found.');
    redirect('admin/public-pages.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $id = request_int('page_id');
    $page = Database::fetch('SELECT * FROM public_pages WHERE id = ?', [$id]);
    if (!$page) {
        flash('error', 'The selected public page could not be found.');
        redirect('admin/public-pages.php');
    }

    $data = [
        'navigation_label' => trim((string) ($_POST['navigation_label'] ?? '')),
        'title' => trim((string) ($_POST['title'] ?? '')),
        'eyebrow' => trim((string) ($_POST['eyebrow'] ?? '')),
        'hero_title' => trim((string) ($_POST['hero_title'] ?? '')),
        'hero_text' => trim((string) ($_POST['hero_text'] ?? '')),
        'content_html' => sanitize_public_html((string) ($_POST['content_html'] ?? '')),
        'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
        'cta_label' => trim((string) ($_POST['cta_label'] ?? '')),
        'cta_url' => trim((string) ($_POST['cta_url'] ?? '')),
        'show_in_header' => isset($_POST['show_in_header']) ? 1 : 0,
        'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'sort_order' => max(0, min(999, (int) ($_POST['sort_order'] ?? 0))),
    ];

    if (mb_strlen($data['navigation_label']) < 2 || mb_strlen($data['navigation_label']) > 100) $errors['navigation_label'] = 'Use a navigation label between 2 and 100 characters.';
    if (mb_strlen($data['title']) < 2 || mb_strlen($data['title']) > 160) $errors['title'] = 'Use a page title between 2 and 160 characters.';
    if (mb_strlen($data['eyebrow']) > 120) $errors['eyebrow'] = 'Keep the eyebrow text within 120 characters.';
    if (mb_strlen($data['hero_title']) < 8 || mb_strlen($data['hero_title']) > 220) $errors['hero_title'] = 'Use a hero heading between 8 and 220 characters.';
    if (mb_strlen($data['hero_text']) < 20 || mb_strlen($data['hero_text']) > 1000) $errors['hero_text'] = 'Use supporting text between 20 and 1,000 characters.';
    if (mb_strlen(trim(strip_tags($data['content_html']))) < 40) $errors['content_html'] = 'Write useful page content of at least 40 characters.';
    if (mb_strlen($data['meta_description']) < 20 || mb_strlen($data['meta_description']) > 320) $errors['meta_description'] = 'Use a search description between 20 and 320 characters.';
    if (mb_strlen($data['cta_label']) > 100) $errors['cta_label'] = 'Keep the action label within 100 characters.';
    if (mb_strlen($data['cta_url']) > 255) $errors['cta_url'] = 'Keep the action link within 255 characters.';
    if (($data['cta_label'] === '') !== ($data['cta_url'] === '')) $errors['cta_url'] = 'Provide both an action label and action link, or leave both empty.';
    if ($data['cta_url'] !== '' && PublicPages::resolveUrl($data['cta_url']) === '') $errors['cta_url'] = 'Use a valid local path, HTTPS address, email link, telephone link or page anchor.';

    if (!$errors) {
        Database::query(
            'UPDATE public_pages SET navigation_label=?, title=?, eyebrow=?, hero_title=?, hero_text=?, content_html=?, meta_description=?, cta_label=?, cta_url=?, show_in_header=?, show_in_footer=?, is_published=?, sort_order=?, updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?',
            [
                $data['navigation_label'], $data['title'], $data['eyebrow'], $data['hero_title'],
                $data['hero_text'], $data['content_html'], $data['meta_description'],
                $data['cta_label'] ?: null, $data['cta_url'] ?: null, $data['show_in_header'],
                $data['show_in_footer'], $data['is_published'], $data['sort_order'],
                (int) current_user()['id'], $id,
            ]
        );
        PublicPages::reset();
        activity('public_page_updated', ['page_id' => $id, 'slug' => $page['slug']]);
        flash('success', 'The public page was updated successfully.');
        redirect('admin/public-pages.php');
    }
}

$values = array_merge($page, is_post() ? $_POST : []);
$editorHtml = sanitize_public_html((string) ($values['content_html'] ?? ''));
$pageTitle = 'Edit ' . (string) $page['title'];
$bodyClass = 'admin-public-page-editor-page';
$pageStyles = ['curriculum.css'];
$pageScript = 'curriculum.js';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro curriculum-editor-intro">
    <div>
        <a class="back-link" href="<?= e(url('admin/public-pages.php')) ?>"><?= icon('arrow-left') ?>Public pages</a>
        <span class="eyebrow">Public content editor</span>
        <h1>Edit <?= e((string) $page['title']) ?></h1>
        <p>Change the visitor-facing message while keeping the permanent page address intact.</p>
    </div>
    <div class="page-intro-actions"><a class="button button-secondary" href="<?= e(PublicPages::urlFor((string) $page['slug']) . '?preview=1') ?>">Preview page</a></div>
</section>

<?php if ($errors): ?>
<div class="alert alert-danger curriculum-validation-summary" role="alert"><?= icon('alert-circle') ?><div><strong>Review the highlighted fields.</strong><span><?= count($errors) ?> issue<?= count($errors) === 1 ? '' : 's' ?> must be corrected before saving.</span></div></div>
<?php endif; ?>

<form method="post" class="curriculum-editor-layout public-page-editor-layout" data-curriculum-form data-draft-key="public-page-<?= (int) $page['id'] ?>" data-server-updated="<?= e((string) ($page['updated_at'] ?? '')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="page_id" value="<?= (int) $page['id'] ?>">

    <div class="curriculum-editor-main">
        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Page identity</span><h2>Navigation and headings</h2><p>The URL slug is fixed to keep bookmarks and search links working.</p></div></div>
            <div class="form-grid two">
                <div class="field"><label for="page-slug">Permanent page slug</label><input id="page-slug" value="<?= e((string) $page['slug']) ?>" disabled><small class="field-hint"><?= e(PublicPages::urlFor((string) $page['slug'])) ?></small></div>
                <div class="field"><label for="navigation-label">Navigation label</label><input id="navigation-label" name="navigation_label" value="<?= e((string) $values['navigation_label']) ?>" required><?= validation_error($errors, 'navigation_label') ?></div>
            </div>
            <div class="form-grid two">
                <div class="field"><label for="page-title">Browser and page title</label><input id="page-title" name="title" value="<?= e((string) $values['title']) ?>" required><?= validation_error($errors, 'title') ?></div>
                <div class="field"><label for="page-eyebrow">Eyebrow text</label><input id="page-eyebrow" name="eyebrow" value="<?= e((string) $values['eyebrow']) ?>" maxlength="120"></div>
            </div>
            <div class="field"><label for="hero-title">Main heading</label><textarea id="hero-title" name="hero_title" rows="2" maxlength="220" required><?= e((string) $values['hero_title']) ?></textarea><?= validation_error($errors, 'hero_title') ?></div>
            <div class="field"><label for="hero-text">Opening summary</label><textarea id="hero-text" name="hero_text" rows="4" maxlength="1000" required><?= e((string) $values['hero_text']) ?></textarea><?= validation_error($errors, 'hero_text') ?></div>
        </section>

        <section class="panel curriculum-form-section document-editor-section">
            <div class="panel-heading"><div><span class="eyebrow">Page document</span><h2>Formatted content</h2><p>Use headings, paragraphs, lists, links, tables, code blocks and expandable sections. Unsafe markup is removed when the page is saved.</p></div></div>
            <div class="document-editor" data-document-editor>
                <div class="document-toolbar" role="toolbar" aria-label="Public page formatting">
                    <select data-block-format aria-label="Text style"><option value="p">Paragraph</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="h4">Heading 4</option><option value="pre">Code block</option><option value="blockquote">Quote</option></select>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-editor-command="bold" aria-label="Bold"><strong>B</strong></button>
                    <button type="button" data-editor-command="italic" aria-label="Italic"><em>I</em></button>
                    <button type="button" data-editor-command="insertUnorderedList" aria-label="Bulleted list">• List</button>
                    <button type="button" data-editor-command="insertOrderedList" aria-label="Numbered list">1. List</button>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-editor-action="link" aria-label="Insert link">Link</button>
                    <button type="button" data-editor-action="table" aria-label="Insert table">Table</button>
                    <button type="button" data-editor-command="removeFormat" aria-label="Clear formatting">Clear</button>
                    <span class="toolbar-divider"></span>
                    <button type="button" data-editor-command="undo" aria-label="Undo">Undo</button>
                    <button type="button" data-editor-command="redo" aria-label="Redo">Redo</button>
                    <button type="button" data-editor-action="fullscreen" aria-label="Toggle full screen">Full screen</button>
                </div>
                <div class="document-page" contenteditable="true" spellcheck="true" data-editor-surface aria-label="Public page content editor"><?= $editorHtml ?></div>
                <textarea name="content_html" data-editor-input hidden><?= e($editorHtml) ?></textarea>
                <div class="document-statusbar"><span data-word-count>0 words</span><span data-character-count>0 characters</span><span>Local draft protection</span></div>
            </div>
            <?= validation_error($errors, 'content_html') ?>
        </section>

        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Search and action</span><h2>Page discovery</h2><p>Describe the page accurately and optionally add one clear action.</p></div></div>
            <div class="field"><label for="meta-description">Search description</label><textarea id="meta-description" name="meta_description" rows="3" maxlength="320" required><?= e((string) $values['meta_description']) ?></textarea><?= validation_error($errors, 'meta_description') ?></div>
            <div class="form-grid two">
                <div class="field"><label for="cta-label">Action label</label><input id="cta-label" name="cta_label" value="<?= e((string) ($values['cta_label'] ?? '')) ?>" maxlength="100"></div>
                <div class="field"><label for="cta-url">Action link</label><input id="cta-url" name="cta_url" value="<?= e((string) ($values['cta_url'] ?? '')) ?>" maxlength="255"><small class="field-hint">Examples: help.php, index.php#learning, mailto:{{support_email}}</small><?= validation_error($errors, 'cta_url') ?></div>
            </div>
        </section>
    </div>

    <aside class="curriculum-editor-sidebar">
        <section class="panel curriculum-form-section curriculum-sticky-card">
            <div class="panel-heading"><div><span class="eyebrow">Publishing</span><h2>Visibility</h2></div></div>
            <div class="field"><label for="sort-order">Navigation order</label><input id="sort-order" name="sort_order" type="number" min="0" max="999" value="<?= e((string) $values['sort_order']) ?>" required></div>
            <label class="toggle-row"><span><strong>Published</strong><small>Allow visitors to open this page.</small></span><input type="checkbox" name="is_published" <?= !empty($values['is_published']) ? 'checked' : '' ?>><i></i></label>
            <label class="toggle-row"><span><strong>Show in public header</strong><small>Add the page to the main visitor navigation.</small></span><input type="checkbox" name="show_in_header" <?= !empty($values['show_in_header']) ? 'checked' : '' ?>><i></i></label>
            <label class="toggle-row"><span><strong>Show in public footer</strong><small>Add the page to the footer information links.</small></span><input type="checkbox" name="show_in_footer" <?= !empty($values['show_in_footer']) ? 'checked' : '' ?>><i></i></label>
            <button class="button button-large" type="submit"><?= icon('save') ?>Save public page</button>
        </section>
        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Dynamic tokens</span><h2>Available values</h2></div></div>
            <ul class="check-list token-reference-list">
                <li><code>{{site_name}}</code></li>
                <li><code>{{organisation_name}}</code></li>
                <li><code>{{support_email}}</code></li>
                <li><code>{{course_list}}</code></li>
                <li><code>{{language_list}}</code></li>
                <li><code>{{registration_message}}</code></li>
                <li><code>{{current_year}}</code></li>
            </ul>
        </section>
    </aside>
</form>
<?php require base_path('partials/footer.php'); ?>

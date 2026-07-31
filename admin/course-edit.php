<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

$id = request_int('id');
$course = $id > 0 ? Database::fetch('SELECT * FROM courses WHERE id = ?', [$id]) : null;
if ($id > 0 && !$course) {
    flash('error', 'The selected learning path could not be found.');
    redirect('admin/content.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $id = request_int('course_id');
    $course = $id > 0 ? Database::fetch('SELECT * FROM courses WHERE id = ?', [$id]) : null;

    $data = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => strtolower(trim((string) ($_POST['slug'] ?? ''))),
        'short_description' => trim((string) ($_POST['short_description'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'icon' => trim((string) ($_POST['icon'] ?? 'book-open')),
        'colour' => trim((string) ($_POST['colour'] ?? '#5B4BDB')),
        'level' => trim((string) ($_POST['level'] ?? 'Beginner')),
        'estimated_time' => trim((string) ($_POST['estimated_time'] ?? '')),
        'audience' => trim((string) ($_POST['audience'] ?? '')),
        'outcomes' => trim((string) ($_POST['outcomes'] ?? '')),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    if (mb_strlen($data['title']) < 4) $errors['title'] = 'Enter a learning-path title of at least four characters.';
    if (!preg_match('/^[a-z0-9-]{3,150}$/', $data['slug'])) $errors['slug'] = 'Use lowercase letters, numbers and dashes only.';
    if (mb_strlen($data['short_description']) < 20) $errors['short_description'] = 'Write a useful short description of at least 20 characters.';
    if (mb_strlen($data['description']) < 40) $errors['description'] = 'Write a complete path description of at least 40 characters.';
    if (mb_strlen($data['outcomes']) < 20) $errors['outcomes'] = 'Add clear learning outcomes, one per line.';
    if (mb_strlen($data['estimated_time']) < 2) $errors['estimated_time'] = 'Enter the expected completion time.';
    if (mb_strlen($data['audience']) < 2) $errors['audience'] = 'Enter the intended learner audience.';
    if (!preg_match('/^#[0-9a-f]{6}$/i', $data['colour'])) $errors['colour'] = 'Choose a valid path colour.';

    $slugOwner = Database::fetch('SELECT id FROM courses WHERE slug = ? AND id <> ?', [$data['slug'], $id]);
    if ($slugOwner) $errors['slug'] = 'That learning-path URL slug is already in use.';

    if (!$errors) {
        $params = array_values($data);
        if ($id > 0) {
            $params[] = $id;
            Database::query('UPDATE courses SET title=?, slug=?, short_description=?, description=?, icon=?, colour=?, level=?, estimated_time=?, audience=?, outcomes=?, sort_order=?, is_published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', $params);
        } else {
            Database::query('INSERT INTO courses (title, slug, short_description, description, icon, colour, level, estimated_time, audience, outcomes, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $params);
            $id = (int) Database::connection()->lastInsertId();
        }

        activity('course_saved', ['course_id' => $id]);
        flash('success', 'The learning path was saved successfully.');
        redirect('admin/content.php');
    }
}

$values = array_merge([
    'id' => 0,
    'title' => '',
    'slug' => '',
    'short_description' => '',
    'description' => '',
    'icon' => 'book-open',
    'colour' => '#5B4BDB',
    'level' => 'Beginner',
    'estimated_time' => '2 hours',
    'audience' => 'Ages 8–17',
    'outcomes' => '',
    'sort_order' => 10,
    'is_published' => 0,
    'updated_at' => '',
], $course ?: [], is_post() ? $_POST : []);

$isEditing = (int) ($values['id'] ?? 0) > 0;
$pageTitle = $isEditing ? 'Edit learning path' : 'Create learning path';
$bodyClass = 'admin-curriculum-editor-page';
$pageScript = 'curriculum.js';
require base_path('partials/header.php');
?>
<link rel="stylesheet" href="<?= e(asset('css/curriculum.css')) ?>">

<section class="workspace-section page-intro curriculum-editor-intro">
    <div>
        <a class="back-link" href="<?= e(url('admin/content.php')) ?>"><?= icon('arrow-left') ?>Curriculum management</a>
        <span class="eyebrow">Learning path editor</span>
        <h1><?= $isEditing ? 'Edit learning path' : 'Create learning path' ?></h1>
        <p>Define the path identity, learner audience, outcomes and publication state on a dedicated page.</p>
    </div>
    <?php if ($isEditing): ?><a class="button button-secondary" href="<?= e(url('course.php?course=' . urlencode((string) $values['slug']))) ?>">Preview path</a><?php endif; ?>
</section>

<?php if ($errors): ?>
<div class="alert alert-danger curriculum-validation-summary" role="alert"><?= icon('alert-circle') ?><div><strong>Review the highlighted fields.</strong><span><?= count($errors) ?> issue<?= count($errors) === 1 ? '' : 's' ?> must be corrected before saving.</span></div></div>
<?php endif; ?>

<form method="post" class="curriculum-editor-layout" data-curriculum-form data-draft-key="course-<?= (int) ($values['id'] ?? 0) ?>" data-server-updated="<?= e((string) ($values['updated_at'] ?? '')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="course_id" value="<?= (int) ($values['id'] ?? 0) ?>">

    <div class="curriculum-editor-main">
        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Identity</span><h2>Path information</h2><p>Use a clear name and description that learners can understand quickly.</p></div></div>
            <div class="form-grid two">
                <div class="field"><label for="course-title">Learning-path title</label><input id="course-title" name="title" value="<?= e((string) $values['title']) ?>" data-slug-source required><?= validation_error($errors, 'title') ?></div>
                <div class="field"><label for="course-slug">URL slug</label><input id="course-slug" name="slug" value="<?= e((string) $values['slug']) ?>" data-slug-target required><small class="field-hint">Example: introduction-to-python</small><?= validation_error($errors, 'slug') ?></div>
            </div>
            <div class="field"><label for="course-short">Short description</label><textarea id="course-short" name="short_description" rows="3" maxlength="300" required><?= e((string) $values['short_description']) ?></textarea><small class="field-hint">Used on curriculum cards and learner dashboards.</small><?= validation_error($errors, 'short_description') ?></div>
            <div class="field"><label for="course-description">Full description</label><textarea id="course-description" name="description" rows="7" required><?= e((string) $values['description']) ?></textarea><?= validation_error($errors, 'description') ?></div>
        </section>

        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Learning design</span><h2>Audience and outcomes</h2><p>Describe who the path is for and what they will achieve.</p></div></div>
            <div class="form-grid three">
                <div class="field"><label for="course-level">Level</label><select id="course-level" name="level"><?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $level): ?><option value="<?= e($level) ?>" <?= (string) $values['level'] === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="course-time">Estimated completion time</label><input id="course-time" name="estimated_time" value="<?= e((string) $values['estimated_time']) ?>" required><?= validation_error($errors, 'estimated_time') ?></div>
                <div class="field"><label for="course-audience">Audience</label><input id="course-audience" name="audience" value="<?= e((string) $values['audience']) ?>" required><?= validation_error($errors, 'audience') ?></div>
            </div>
            <div class="field"><label for="course-outcomes">Learning outcomes</label><textarea id="course-outcomes" name="outcomes" rows="8" required><?= e((string) $values['outcomes']) ?></textarea><small class="field-hint">Enter one measurable outcome per line.</small><?= validation_error($errors, 'outcomes') ?></div>
        </section>
    </div>

    <aside class="curriculum-editor-sidebar">
        <section class="panel curriculum-form-section curriculum-sticky-card">
            <div class="panel-heading"><div><span class="eyebrow">Publishing</span><h2>Path settings</h2></div></div>
            <div class="field"><label for="course-icon">Icon key</label><input id="course-icon" name="icon" value="<?= e((string) $values['icon']) ?>" required></div>
            <div class="field"><label for="course-colour">Path colour</label><div class="colour-field"><input id="course-colour" name="colour" type="color" value="<?= e((string) $values['colour']) ?>"><span data-colour-value><?= e((string) $values['colour']) ?></span></div><?= validation_error($errors, 'colour') ?></div>
            <div class="field"><label for="course-order">Display order</label><input id="course-order" name="sort_order" type="number" min="0" value="<?= e((string) $values['sort_order']) ?>" required></div>
            <label class="toggle-row curriculum-publish-toggle"><span><strong>Publish this path</strong><small>Make it available to learners immediately after saving.</small></span><input type="checkbox" name="is_published" <?= (int) ($values['is_published'] ?? 0) === 1 ? 'checked' : '' ?>><i></i></label>
            <div class="draft-status" data-draft-status aria-live="polite">Draft protection ready</div>
            <div class="curriculum-page-actions"><a class="button button-secondary" href="<?= e(url('admin/content.php')) ?>">Cancel</a><button class="button" type="submit"><?= $isEditing ? 'Save changes' : 'Create learning path' ?></button></div>
        </section>
    </aside>
</form>

<?php require base_path('partials/footer.php'); ?>

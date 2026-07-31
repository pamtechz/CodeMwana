<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

$id = request_int('id');
$lesson = $id > 0 ? Database::fetch('SELECT * FROM lessons WHERE id = ?', [$id]) : null;
if ($id > 0 && !$lesson) {
    flash('error', 'The selected lesson could not be found.');
    redirect('admin/content.php');
}

$courses = Database::fetchAll('SELECT id, title, is_published FROM courses ORDER BY sort_order, title');
if (!$courses) {
    flash('error', 'Create a learning path before creating a lesson.');
    redirect('admin/course-edit.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $id = request_int('lesson_id');
    $lesson = $id > 0 ? Database::fetch('SELECT * FROM lessons WHERE id = ?', [$id]) : null;

    $data = [
        'course_id' => request_int('course_id'),
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => strtolower(trim((string) ($_POST['slug'] ?? ''))),
        'summary' => trim((string) ($_POST['summary'] ?? '')),
        'learning_objective' => trim((string) ($_POST['learning_objective'] ?? '')),
        'concepts' => trim((string) ($_POST['concepts'] ?? '')),
        'vocabulary' => trim((string) ($_POST['vocabulary'] ?? '')),
        'content_html' => sanitize_lesson_html((string) ($_POST['content_html'] ?? '')),
        'challenge_text' => trim((string) ($_POST['challenge_text'] ?? '')),
        'starter_code' => (string) ($_POST['starter_code'] ?? ''),
        'expected_output' => trim((string) ($_POST['expected_output'] ?? '')),
        'teacher_note' => trim((string) ($_POST['teacher_note'] ?? '')),
        'icon' => trim((string) ($_POST['icon'] ?? 'book-open')),
        'difficulty' => trim((string) ($_POST['difficulty'] ?? 'beginner')),
        'duration_minutes' => max(5, min(180, (int) ($_POST['duration_minutes'] ?? 15))),
        'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    if (!$data['course_id'] || !Database::fetch('SELECT id FROM courses WHERE id = ?', [$data['course_id']])) $errors['course_id'] = 'Select an existing learning path.';
    if (mb_strlen($data['title']) < 4) $errors['title'] = 'Enter a lesson title of at least four characters.';
    if (!preg_match('/^[a-z0-9-]{3,170}$/', $data['slug'])) $errors['slug'] = 'Use lowercase letters, numbers and dashes only.';
    if (mb_strlen($data['summary']) < 20) $errors['summary'] = 'Write a useful lesson summary of at least 20 characters.';
    if (mb_strlen($data['learning_objective']) < 20) $errors['learning_objective'] = 'Write a measurable learning objective.';
    if (mb_strlen($data['concepts']) < 3) $errors['concepts'] = 'List the main concepts covered.';
    if (mb_strlen($data['vocabulary']) < 3) $errors['vocabulary'] = 'List important vocabulary.';
    if (mb_strlen(trim(strip_tags($data['content_html']))) < 40) $errors['content_html'] = 'Write complete lesson content of at least 40 characters.';
    if (mb_strlen($data['challenge_text']) < 20) $errors['challenge_text'] = 'Write a practical learner challenge.';
    if (mb_strlen($data['teacher_note']) < 10) $errors['teacher_note'] = 'Add a useful teacher note.';
    if (!in_array($data['difficulty'], ['beginner', 'intermediate', 'advanced'], true)) $errors['difficulty'] = 'Select a valid difficulty level.';

    $slugOwner = Database::fetch('SELECT id FROM lessons WHERE slug = ? AND id <> ?', [$data['slug'], $id]);
    if ($slugOwner) $errors['slug'] = 'That lesson URL slug is already in use.';

    if (!$errors) {
        $params = array_values($data);
        if ($id > 0) {
            $params[] = $id;
            Database::query('UPDATE lessons SET course_id=?, title=?, slug=?, summary=?, learning_objective=?, concepts=?, vocabulary=?, content_html=?, challenge_text=?, starter_code=?, expected_output=?, teacher_note=?, icon=?, difficulty=?, duration_minutes=?, sort_order=?, is_published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', $params);
        } else {
            Database::query('INSERT INTO lessons (course_id, title, slug, summary, learning_objective, concepts, vocabulary, content_html, challenge_text, starter_code, expected_output, teacher_note, icon, difficulty, duration_minutes, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $params);
            $id = (int) Database::connection()->lastInsertId();
        }

        activity('lesson_saved', ['lesson_id' => $id]);
        flash('success', 'The lesson was saved successfully.');
        redirect('admin/content.php');
    }
}

$values = array_merge([
    'id' => 0,
    'course_id' => request_int('course'),
    'title' => '',
    'slug' => '',
    'summary' => '',
    'learning_objective' => '',
    'concepts' => '',
    'vocabulary' => '',
    'content_html' => '<h2>Lesson introduction</h2><p>Write the lesson explanation here.</p>',
    'challenge_text' => '',
    'starter_code' => '',
    'expected_output' => '',
    'teacher_note' => '',
    'icon' => 'book-open',
    'difficulty' => 'beginner',
    'duration_minutes' => 15,
    'sort_order' => 10,
    'is_published' => 0,
    'updated_at' => '',
], $lesson ?: [], is_post() ? $_POST : []);

$isEditing = (int) ($values['id'] ?? 0) > 0;
$editorHtml = sanitize_lesson_html((string) ($values['content_html'] ?? ''));
$pageTitle = $isEditing ? 'Edit lesson' : 'Create lesson';
$bodyClass = 'admin-curriculum-editor-page lesson-maker-page';
$pageScript = 'curriculum.js';
require base_path('partials/header.php');
?>
<link rel="stylesheet" href="<?= e(asset('css/curriculum.css')) ?>">

<section class="workspace-section page-intro curriculum-editor-intro">
    <div>
        <a class="back-link" href="<?= e(url('admin/content.php')) ?>"><?= icon('arrow-left') ?>Curriculum management</a>
        <span class="eyebrow">Curriculum lesson maker</span>
        <h1><?= $isEditing ? 'Edit curriculum lesson' : 'Create curriculum lesson' ?></h1>
        <p>Write, format and organise the complete learner lesson from a dedicated document-style page.</p>
    </div>
    <?php if ($isEditing): ?><div class="page-intro-actions"><a class="button button-secondary" href="<?= e(url('admin/questions.php?lesson=' . (int) $values['id'])) ?>">Assessment</a><a class="button button-secondary" href="<?= e(url('lesson.php?lesson=' . urlencode((string) $values['slug']))) ?>">Preview lesson</a></div><?php endif; ?>
</section>

<?php if ($errors): ?>
<div class="alert alert-danger curriculum-validation-summary" role="alert"><?= icon('alert-circle') ?><div><strong>Review the highlighted fields.</strong><span><?= count($errors) ?> issue<?= count($errors) === 1 ? '' : 's' ?> must be corrected before saving.</span></div></div>
<?php endif; ?>

<form method="post" class="curriculum-editor-layout lesson-editor-layout" data-curriculum-form data-draft-key="lesson-<?= (int) ($values['id'] ?? 0) ?>" data-server-updated="<?= e((string) ($values['updated_at'] ?? '')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="lesson_id" value="<?= (int) ($values['id'] ?? 0) ?>">

    <div class="curriculum-editor-main">
        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Lesson identity</span><h2>Basic information</h2><p>Connect the lesson to its path and state the learning purpose clearly.</p></div></div>
            <div class="form-grid two">
                <div class="field"><label for="lesson-course">Learning path</label><select id="lesson-course" name="course_id" required><option value="">Select a learning path</option><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>" <?= (int) $values['course_id'] === (int) $course['id'] ? 'selected' : '' ?>><?= e($course['title']) ?><?= (int) $course['is_published'] ? '' : ' — Draft' ?></option><?php endforeach; ?></select><?= validation_error($errors, 'course_id') ?></div>
                <div class="field"><label for="lesson-title">Lesson title</label><input id="lesson-title" name="title" value="<?= e((string) $values['title']) ?>" data-slug-source required><?= validation_error($errors, 'title') ?></div>
            </div>
            <div class="form-grid two">
                <div class="field"><label for="lesson-slug">URL slug</label><input id="lesson-slug" name="slug" value="<?= e((string) $values['slug']) ?>" data-slug-target required><?= validation_error($errors, 'slug') ?></div>
                <div class="field"><label for="lesson-objective">Learning objective</label><input id="lesson-objective" name="learning_objective" value="<?= e((string) $values['learning_objective']) ?>" required><?= validation_error($errors, 'learning_objective') ?></div>
            </div>
            <div class="field"><label for="lesson-summary">Lesson summary</label><textarea id="lesson-summary" name="summary" rows="3" maxlength="320" required><?= e((string) $values['summary']) ?></textarea><?= validation_error($errors, 'summary') ?></div>
            <div class="form-grid two">
                <div class="field"><label for="lesson-concepts">Key concepts</label><input id="lesson-concepts" name="concepts" value="<?= e((string) $values['concepts']) ?>" required><small class="field-hint">Separate concepts with commas.</small><?= validation_error($errors, 'concepts') ?></div>
                <div class="field"><label for="lesson-vocabulary">Vocabulary</label><input id="lesson-vocabulary" name="vocabulary" value="<?= e((string) $values['vocabulary']) ?>" required><small class="field-hint">Separate terms with commas.</small><?= validation_error($errors, 'vocabulary') ?></div>
            </div>
        </section>

        <section class="panel curriculum-form-section document-editor-section">
            <div class="panel-heading"><div><span class="eyebrow">Lesson document</span><h2>Content editor</h2><p>Use the toolbar to format the lesson like a document. Content is cleaned securely before saving.</p></div></div>
            <div class="document-editor" data-document-editor>
                <div class="document-toolbar" role="toolbar" aria-label="Lesson document formatting">
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
                <div class="document-page" contenteditable="true" spellcheck="true" data-editor-surface aria-label="Lesson content editor"><?= $editorHtml ?></div>
                <textarea id="lesson-content" name="content_html" data-editor-input hidden><?= e($editorHtml) ?></textarea>
                <div class="document-statusbar"><span data-word-count>0 words</span><span data-character-count>0 characters</span><span>Autosave enabled</span></div>
            </div>
            <?= validation_error($errors, 'content_html') ?>
        </section>

        <section class="panel curriculum-form-section">
            <div class="panel-heading"><div><span class="eyebrow">Practice</span><h2>Challenge and code</h2><p>Give learners a practical task and an optional starting point.</p></div></div>
            <div class="field"><label for="lesson-challenge">Practical challenge</label><textarea id="lesson-challenge" name="challenge_text" rows="5" required><?= e((string) $values['challenge_text']) ?></textarea><?= validation_error($errors, 'challenge_text') ?></div>
            <div class="form-grid two">
                <div class="field"><label for="lesson-starter">Starter code</label><textarea id="lesson-starter" class="code-textarea" name="starter_code" rows="11" spellcheck="false"><?= e((string) $values['starter_code']) ?></textarea></div>
                <div class="field"><label for="lesson-output">Expected output</label><textarea id="lesson-output" class="code-textarea" name="expected_output" rows="11" spellcheck="false"><?= e((string) $values['expected_output']) ?></textarea></div>
            </div>
            <div class="field"><label for="lesson-note">Teacher note</label><textarea id="lesson-note" name="teacher_note" rows="5" required><?= e((string) $values['teacher_note']) ?></textarea><?= validation_error($errors, 'teacher_note') ?></div>
        </section>
    </div>

    <aside class="curriculum-editor-sidebar">
        <section class="panel curriculum-form-section curriculum-sticky-card">
            <div class="panel-heading"><div><span class="eyebrow">Publishing</span><h2>Lesson settings</h2></div></div>
            <div class="field"><label for="lesson-icon">Icon key</label><input id="lesson-icon" name="icon" value="<?= e((string) $values['icon']) ?>" required></div>
            <div class="field"><label for="lesson-difficulty">Difficulty</label><select id="lesson-difficulty" name="difficulty"><?php foreach (['beginner', 'intermediate', 'advanced'] as $level): ?><option value="<?= e($level) ?>" <?= (string) $values['difficulty'] === $level ? 'selected' : '' ?>><?= e(ucfirst($level)) ?></option><?php endforeach; ?></select><?= validation_error($errors, 'difficulty') ?></div>
            <div class="form-grid two compact-editor-fields"><div class="field"><label for="lesson-duration">Minutes</label><input id="lesson-duration" name="duration_minutes" type="number" min="5" max="180" value="<?= e((string) $values['duration_minutes']) ?>" required></div><div class="field"><label for="lesson-order">Order</label><input id="lesson-order" name="sort_order" type="number" min="0" value="<?= e((string) $values['sort_order']) ?>" required></div></div>
            <label class="toggle-row curriculum-publish-toggle"><span><strong>Publish this lesson</strong><small>Learners can access it after saving.</small></span><input type="checkbox" name="is_published" <?= (int) ($values['is_published'] ?? 0) === 1 ? 'checked' : '' ?>><i></i></label>
            <div class="draft-status" data-draft-status aria-live="polite">Draft protection ready</div>
            <div class="curriculum-page-actions"><a class="button button-secondary" href="<?= e(url('admin/content.php')) ?>">Cancel</a><button class="button" type="submit"><?= $isEditing ? 'Save lesson changes' : 'Create lesson' ?></button></div>
        </section>
    </aside>
</form>

<?php require base_path('partials/footer.php'); ?>

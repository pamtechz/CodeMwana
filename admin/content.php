<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

$errors = [];
$editCourseId = request_int('edit_course');
$editLessonId = request_int('edit_lesson');

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_course') {
        $id = request_int('course_id');
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
        if (mb_strlen($data['title']) < 4) $errors['course'] = 'Enter a learning-path title of at least four characters.';
        if (!preg_match('/^[a-z0-9-]{3,150}$/', $data['slug'])) $errors['course'] = 'Use a lowercase URL slug containing letters, numbers and dashes.';
        if (mb_strlen($data['short_description']) < 20 || mb_strlen($data['description']) < 40 || mb_strlen($data['outcomes']) < 20) $errors['course'] = 'Provide complete descriptions and learning outcomes.';
        $slugOwner = Database::fetch('SELECT id FROM courses WHERE slug = ? AND id <> ?', [$data['slug'], $id]);
        if ($slugOwner) $errors['course'] = 'That learning-path URL slug is already in use.';

        if (!$errors) {
            $params = array_values($data);
            if ($id) {
                $params[] = $id;
                Database::query('UPDATE courses SET title=?, slug=?, short_description=?, description=?, icon=?, colour=?, level=?, estimated_time=?, audience=?, outcomes=?, sort_order=?, is_published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', $params);
            } else {
                Database::query('INSERT INTO courses (title, slug, short_description, description, icon, colour, level, estimated_time, audience, outcomes, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $params);
                $id = (int) Database::connection()->lastInsertId();
            }
            activity('course_saved', ['course_id' => $id]);
            flash('success', 'The learning path was saved.');
            redirect('admin/content.php');
        }
        $editCourseId = $id;
    }

    if ($action === 'toggle_course') {
        $id = request_int('course_id');
        Database::query('UPDATE courses SET is_published=CASE WHEN is_published=1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
        activity('course_visibility_changed', ['course_id' => $id]);
        flash('success', 'Learning-path publication status was changed.');
        redirect('admin/content.php');
    }

    if ($action === 'save_lesson') {
        $id = request_int('lesson_id');
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
            'expected_output' => (string) ($_POST['expected_output'] ?? ''),
            'teacher_note' => trim((string) ($_POST['teacher_note'] ?? '')),
            'icon' => trim((string) ($_POST['icon'] ?? 'book-open')),
            'difficulty' => trim((string) ($_POST['difficulty'] ?? 'beginner')),
            'duration_minutes' => max(5, min(180, (int) ($_POST['duration_minutes'] ?? 15))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
        if (!$data['course_id'] || !Database::fetch('SELECT id FROM courses WHERE id=?', [$data['course_id']])) $errors['lesson'] = 'Select an existing learning path.';
        if (mb_strlen($data['title']) < 4 || !preg_match('/^[a-z0-9-]{3,170}$/', $data['slug'])) $errors['lesson'] = 'Enter a valid lesson title and lowercase URL slug.';
        if (mb_strlen($data['summary']) < 20 || mb_strlen($data['learning_objective']) < 20 || mb_strlen($data['content_html']) < 40 || mb_strlen($data['challenge_text']) < 20 || mb_strlen($data['teacher_note']) < 10) $errors['lesson'] = 'Complete the objective, summary, lesson content, challenge and teacher note.';
        $slugOwner = Database::fetch('SELECT id FROM lessons WHERE slug=? AND id<>?', [$data['slug'], $id]);
        if ($slugOwner) $errors['lesson'] = 'That lesson URL slug is already in use.';

        if (!$errors) {
            $params = array_values($data);
            if ($id) {
                $params[] = $id;
                Database::query('UPDATE lessons SET course_id=?, title=?, slug=?, summary=?, learning_objective=?, concepts=?, vocabulary=?, content_html=?, challenge_text=?, starter_code=?, expected_output=?, teacher_note=?, icon=?, difficulty=?, duration_minutes=?, sort_order=?, is_published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', $params);
            } else {
                Database::query('INSERT INTO lessons (course_id, title, slug, summary, learning_objective, concepts, vocabulary, content_html, challenge_text, starter_code, expected_output, teacher_note, icon, difficulty, duration_minutes, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $params);
                $id = (int) Database::connection()->lastInsertId();
            }
            activity('lesson_saved', ['lesson_id' => $id]);
            flash('success', 'The lesson was saved. Add or review its assessment questions next.');
            redirect('admin/questions.php?lesson=' . $id);
        }
        $editLessonId = $id;
    }

    if ($action === 'toggle_lesson') {
        $id = request_int('lesson_id');
        Database::query('UPDATE lessons SET is_published=CASE WHEN is_published=1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
        activity('lesson_visibility_changed', ['lesson_id' => $id]);
        flash('success', 'Lesson publication status was changed.');
        redirect('admin/content.php');
    }
}

$courses = Database::fetchAll('SELECT c.*, COUNT(l.id) AS lesson_count FROM courses c LEFT JOIN lessons l ON l.course_id=c.id GROUP BY c.id ORDER BY c.sort_order,c.id');
$lessons = Database::fetchAll('SELECT l.*, c.title AS course_title, COUNT(q.id) AS question_count FROM lessons l JOIN courses c ON c.id=l.course_id LEFT JOIN quiz_questions q ON q.lesson_id=l.id GROUP BY l.id ORDER BY c.sort_order,l.sort_order,l.id LIMIT 200');
$editCourse = $editCourseId ? Database::fetch('SELECT * FROM courses WHERE id=?', [$editCourseId]) : null;
$editLesson = $editLessonId ? Database::fetch('SELECT * FROM lessons WHERE id=?', [$editLessonId]) : null;

$pageTitle = 'Curriculum management';
$bodyClass = 'admin-content-page';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro"><div><a class="back-link" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('arrow-left') ?>Administration</a><span class="eyebrow">Database curriculum</span><h1>Curriculum management</h1><p>Create, revise and publish learning paths, lessons and assessment questions. Learner pages use these records directly.</p></div><div class="page-intro-actions"><button class="button button-secondary" type="button" data-modal-open="lesson-modal"><?= icon('plus') ?>New lesson</button><button class="button" type="button" data-modal-open="course-modal"><?= icon('plus') ?>New path</button></div></section>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($error) ?></span></div><?php endforeach; ?>
<div class="management-grid">
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">Learning paths</span><h2><?= count($courses) ?> curriculum paths</h2></div></div><div class="content-list">
<?php foreach ($courses as $course): ?><article><span class="course-icon compact" style="--course:<?= e($course['colour']) ?>"><?= icon($course['icon']) ?></span><div><strong><?= e($course['title']) ?></strong><small><?= (int) $course['lesson_count'] ?> lessons · order <?= (int) $course['sort_order'] ?></small></div><span class="status-badge <?= (int) $course['is_published'] ? 'success' : 'warning' ?>"><?= (int) $course['is_published'] ? 'Published' : 'Draft' ?></span><div class="row-actions"><a class="icon-button" href="<?= e(url('admin/content.php?edit_course=' . (int) $course['id'])) ?>" aria-label="Edit <?= e($course['title']) ?>"><?= icon('edit') ?></a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_course"><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><button class="button button-small button-secondary" type="submit"><?= (int) $course['is_published'] ? 'Unpublish' : 'Publish' ?></button></form></div></article><?php endforeach; ?>
</div></section>
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">Lessons and assessments</span><h2><?= count($lessons) ?> lesson records</h2></div></div><div class="content-list lessons">
<?php foreach ($lessons as $lesson): ?><article><span class="lesson-sequence"><?= (int) $lesson['sort_order'] ?></span><div><strong><?= e($lesson['title']) ?></strong><small><?= e($lesson['course_title']) ?> · <?= (int) $lesson['duration_minutes'] ?> min · <?= (int) $lesson['question_count'] ?> questions</small></div><span class="status-badge <?= (int) $lesson['is_published'] ? 'success' : 'warning' ?>"><?= (int) $lesson['is_published'] ? 'Published' : 'Draft' ?></span><div class="row-actions"><a class="icon-button" href="<?= e(url('admin/questions.php?lesson=' . (int) $lesson['id'])) ?>" aria-label="Manage questions for <?= e($lesson['title']) ?>"><?= icon('check-circle') ?></a><a class="icon-button" href="<?= e(url('admin/content.php?edit_lesson=' . (int) $lesson['id'])) ?>" aria-label="Edit <?= e($lesson['title']) ?>"><?= icon('edit') ?></a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_lesson"><input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>"><button class="button button-small button-secondary" type="submit"><?= (int) $lesson['is_published'] ? 'Unpublish' : 'Publish' ?></button></form></div></article><?php endforeach; ?>
</div></section>
</div>

<dialog class="modal" id="course-modal" data-modal <?= $editCourse || isset($errors['course']) ? 'open' : '' ?>><div class="modal-card large"><div class="modal-head"><div><span class="eyebrow">Curriculum path</span><h2><?= $editCourse ? 'Edit learning path' : 'Create learning path' ?></h2></div><a class="icon-button" href="<?= e(url('admin/content.php')) ?>" aria-label="Close form"><?= icon('x') ?></a></div><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="save_course"><input type="hidden" name="course_id" value="<?= (int) ($editCourse['id'] ?? 0) ?>"><div class="form-grid two"><div class="field"><label for="course-title">Title</label><input id="course-title" name="title" value="<?= e($_POST['title'] ?? $editCourse['title'] ?? '') ?>" required></div><div class="field"><label for="course-slug">URL slug</label><input id="course-slug" name="slug" value="<?= e($_POST['slug'] ?? $editCourse['slug'] ?? '') ?>" required></div></div><div class="field"><label for="course-short">Short description</label><textarea id="course-short" name="short_description" rows="2" required><?= e($_POST['short_description'] ?? $editCourse['short_description'] ?? '') ?></textarea></div><div class="field"><label for="course-description">Full description</label><textarea id="course-description" name="description" rows="4" required><?= e($_POST['description'] ?? $editCourse['description'] ?? '') ?></textarea></div><div class="form-grid three"><div class="field"><label for="course-level">Level</label><input id="course-level" name="level" value="<?= e($_POST['level'] ?? $editCourse['level'] ?? 'Beginner') ?>" required></div><div class="field"><label for="course-time">Estimated time</label><input id="course-time" name="estimated_time" value="<?= e($_POST['estimated_time'] ?? $editCourse['estimated_time'] ?? '') ?>" required></div><div class="field"><label for="course-audience">Audience</label><input id="course-audience" name="audience" value="<?= e($_POST['audience'] ?? $editCourse['audience'] ?? 'Ages 8–17') ?>" required></div></div><div class="form-grid three"><div class="field"><label for="course-icon">Icon key</label><input id="course-icon" name="icon" value="<?= e($_POST['icon'] ?? $editCourse['icon'] ?? 'book-open') ?>" required></div><div class="field"><label for="course-colour">Colour</label><input id="course-colour" name="colour" type="color" value="<?= e($_POST['colour'] ?? $editCourse['colour'] ?? '#5B4BDB') ?>" required></div><div class="field"><label for="course-order">Sort order</label><input id="course-order" name="sort_order" type="number" value="<?= e((string) ($_POST['sort_order'] ?? $editCourse['sort_order'] ?? 10)) ?>" required></div></div><div class="field"><label for="course-outcomes">Learning outcomes, one per line</label><textarea id="course-outcomes" name="outcomes" rows="4" required><?= e($_POST['outcomes'] ?? $editCourse['outcomes'] ?? '') ?></textarea></div><label class="consent-check"><input type="checkbox" name="is_published" <?= isset($_POST['action']) ? (isset($_POST['is_published']) ? 'checked' : '') : ((int) ($editCourse['is_published'] ?? 1) ? 'checked' : '') ?>><span>Publish this path</span></label><div class="modal-actions"><a class="button button-secondary" href="<?= e(url('admin/content.php')) ?>">Cancel</a><button class="button" type="submit">Save learning path</button></div></form></div></dialog>

<dialog class="modal" id="lesson-modal" data-modal <?= $editLesson || isset($errors['lesson']) ? 'open' : '' ?>><div class="modal-card large"><div class="modal-head"><div><span class="eyebrow">Curriculum lesson</span><h2><?= $editLesson ? 'Edit lesson' : 'Create lesson' ?></h2></div><a class="icon-button" href="<?= e(url('admin/content.php')) ?>" aria-label="Close form"><?= icon('x') ?></a></div><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="save_lesson"><input type="hidden" name="lesson_id" value="<?= (int) ($editLesson['id'] ?? 0) ?>"><div class="form-grid two"><div class="field"><label for="lesson-course">Learning path</label><select id="lesson-course" name="course_id" required><option value="">Select path</option><?php $selectedCourse = (int) ($_POST['course_id'] ?? $editLesson['course_id'] ?? 0); foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>" <?= $selectedCourse === (int) $course['id'] ? 'selected' : '' ?>><?= e($course['title']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="lesson-title">Lesson title</label><input id="lesson-title" name="title" value="<?= e($_POST['title'] ?? $editLesson['title'] ?? '') ?>" required></div></div><div class="form-grid two"><div class="field"><label for="lesson-slug">URL slug</label><input id="lesson-slug" name="slug" value="<?= e($_POST['slug'] ?? $editLesson['slug'] ?? '') ?>" required></div><div class="field"><label for="lesson-objective">Learning objective</label><input id="lesson-objective" name="learning_objective" value="<?= e($_POST['learning_objective'] ?? $editLesson['learning_objective'] ?? '') ?>" required></div></div><div class="field"><label for="lesson-summary">Summary</label><textarea id="lesson-summary" name="summary" rows="2" required><?= e($_POST['summary'] ?? $editLesson['summary'] ?? '') ?></textarea></div><div class="form-grid two"><div class="field"><label for="lesson-concepts">Concepts</label><input id="lesson-concepts" name="concepts" value="<?= e($_POST['concepts'] ?? $editLesson['concepts'] ?? '') ?>" required></div><div class="field"><label for="lesson-vocabulary">Vocabulary</label><input id="lesson-vocabulary" name="vocabulary" value="<?= e($_POST['vocabulary'] ?? $editLesson['vocabulary'] ?? '') ?>" required></div></div><div class="field"><label for="lesson-content">Lesson HTML content</label><textarea id="lesson-content" name="content_html" rows="8" required><?= e($_POST['content_html'] ?? $editLesson['content_html'] ?? '') ?></textarea><small class="field-hint">Allowed: headings, paragraphs, lists, emphasis, links, tables, pre and code. Script and event attributes are removed.</small></div><div class="field"><label for="lesson-challenge">Practical challenge</label><textarea id="lesson-challenge" name="challenge_text" rows="3" required><?= e($_POST['challenge_text'] ?? $editLesson['challenge_text'] ?? '') ?></textarea></div><div class="form-grid two"><div class="field"><label for="lesson-starter">Starter code</label><textarea id="lesson-starter" name="starter_code" rows="5"><?= e($_POST['starter_code'] ?? $editLesson['starter_code'] ?? '') ?></textarea></div><div class="field"><label for="lesson-output">Expected output</label><textarea id="lesson-output" name="expected_output" rows="5"><?= e($_POST['expected_output'] ?? $editLesson['expected_output'] ?? '') ?></textarea></div></div><div class="field"><label for="lesson-note">Teacher note</label><textarea id="lesson-note" name="teacher_note" rows="3" required><?= e($_POST['teacher_note'] ?? $editLesson['teacher_note'] ?? '') ?></textarea></div><div class="form-grid three"><div class="field"><label for="lesson-icon">Icon key</label><input id="lesson-icon" name="icon" value="<?= e($_POST['icon'] ?? $editLesson['icon'] ?? 'book-open') ?>" required></div><div class="field"><label for="lesson-difficulty">Difficulty</label><select id="lesson-difficulty" name="difficulty"><?php $difficulty = $_POST['difficulty'] ?? $editLesson['difficulty'] ?? 'beginner'; foreach (['beginner','intermediate','advanced'] as $level): ?><option value="<?= $level ?>" <?= $difficulty === $level ? 'selected' : '' ?>><?= e(ucfirst($level)) ?></option><?php endforeach; ?></select></div><div class="field"><label for="lesson-duration">Duration minutes</label><input id="lesson-duration" name="duration_minutes" type="number" min="5" max="180" value="<?= e((string) ($_POST['duration_minutes'] ?? $editLesson['duration_minutes'] ?? 15)) ?>" required></div></div><div class="field"><label for="lesson-order">Sort order</label><input id="lesson-order" name="sort_order" type="number" value="<?= e((string) ($_POST['sort_order'] ?? $editLesson['sort_order'] ?? 10)) ?>" required></div><label class="consent-check"><input type="checkbox" name="is_published" <?= isset($_POST['action']) ? (isset($_POST['is_published']) ? 'checked' : '') : ((int) ($editLesson['is_published'] ?? 0) ? 'checked' : '') ?>><span>Publish this lesson</span></label><div class="modal-actions"><a class="button button-secondary" href="<?= e(url('admin/content.php')) ?>">Cancel</a><button class="button" type="submit">Save lesson</button></div></form></div></dialog>
<?php require base_path('partials/footer.php'); ?>

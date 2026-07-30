<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

$lessonId = request_int('lesson');
$lesson = $lessonId ? Database::fetch('SELECT l.*, c.title AS course_title FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=?', [$lessonId]) : null;
if (!$lesson) { flash('error', 'Select an existing lesson before managing assessment questions.'); redirect('admin/content.php'); }

$errors = [];
$editId = request_int('edit');
if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = request_int('question_id');
        $data = [
            'question' => trim((string) ($_POST['question'] ?? '')),
            'option_a' => trim((string) ($_POST['option_a'] ?? '')),
            'option_b' => trim((string) ($_POST['option_b'] ?? '')),
            'option_c' => trim((string) ($_POST['option_c'] ?? '')),
            'option_d' => trim((string) ($_POST['option_d'] ?? '')),
            'correct_option' => strtoupper(trim((string) ($_POST['correct_option'] ?? ''))),
            'explanation' => trim((string) ($_POST['explanation'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if (mb_strlen($data['question']) < 10) $errors['question'] = 'Write a complete assessment question.';
        foreach (['option_a','option_b','option_c','option_d'] as $option) if (mb_strlen($data[$option]) < 1) $errors['options'] = 'Provide all four answer options.';
        if (!in_array($data['correct_option'], ['A','B','C','D'], true)) $errors['correct'] = 'Select the correct answer.';
        if (mb_strlen($data['explanation']) < 15) $errors['explanation'] = 'Provide a useful explanation for learners.';

        if (!$errors) {
            $params = [$lessonId, ...array_values($data)];
            if ($id) {
                $params[] = $id;
                Database::query('UPDATE quiz_questions SET lesson_id=?, question=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, explanation=?, sort_order=? WHERE id=? AND lesson_id=?', [...$params, $lessonId]);
            } else {
                Database::query('INSERT INTO quiz_questions (lesson_id, question, option_a, option_b, option_c, option_d, correct_option, explanation, sort_order) VALUES (?,?,?,?,?,?,?,?,?)', $params);
                $id = (int) Database::connection()->lastInsertId();
            }
            activity('quiz_question_saved', ['lesson_id' => $lessonId, 'question_id' => $id]);
            flash('success', 'The assessment question was saved.');
            redirect('admin/questions.php?lesson=' . $lessonId);
        }
        $editId = $id;
    }

    if ($action === 'delete') {
        $id = request_int('question_id');
        Database::query('DELETE FROM quiz_questions WHERE id=? AND lesson_id=?', [$id, $lessonId]);
        activity('quiz_question_deleted', ['lesson_id' => $lessonId, 'question_id' => $id]);
        flash('success', 'The assessment question was deleted.');
        redirect('admin/questions.php?lesson=' . $lessonId);
    }
}

$questions = Database::fetchAll('SELECT * FROM quiz_questions WHERE lesson_id=? ORDER BY sort_order,id', [$lessonId]);
$editQuestion = $editId ? Database::fetch('SELECT * FROM quiz_questions WHERE id=? AND lesson_id=?', [$editId, $lessonId]) : null;
$pageTitle = 'Assessment questions';
$bodyClass = 'admin-questions-page';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro"><div><a class="back-link" href="<?= e(url('admin/content.php')) ?>"><?= icon('arrow-left') ?>Curriculum management</a><span class="eyebrow"><?= e($lesson['course_title']) ?></span><h1><?= e($lesson['title']) ?></h1><p>Manage the database-backed assessment shown after this lesson. Every answer includes learner feedback.</p></div><button class="button" type="button" data-modal-open="question-modal"><?= icon('plus') ?>New question</button></section>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($error) ?></span></div><?php endforeach; ?>
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">Assessment inventory</span><h2><?= count($questions) ?> questions</h2><p>A minimum of three varied questions is recommended before publishing the lesson.</p></div><a class="button button-secondary button-small" href="<?= e(url('quiz.php?lesson=' . urlencode($lesson['slug']))) ?>">Preview assessment</a></div>
<?php if (!$questions): ?><div class="empty-state compact"><span><?= icon('check-circle') ?></span><h3>No assessment questions yet</h3><p>Create the first question before learners reach this lesson quiz.</p></div><?php else: ?><div class="question-admin-list"><?php foreach ($questions as $index => $question): ?><article><span class="question-number"><?= $index + 1 ?></span><div><strong><?= e($question['question']) ?></strong><small>Correct answer: <?= e($question['correct_option']) ?> · order <?= (int) $question['sort_order'] ?></small><p><?= e($question['explanation']) ?></p></div><div class="row-actions"><a class="icon-button" href="<?= e(url('admin/questions.php?lesson=' . $lessonId . '&edit=' . (int) $question['id'])) ?>" aria-label="Edit question"><?= icon('edit') ?></a><form method="post" data-confirm="Delete this assessment question permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Delete question"><?= icon('trash') ?></button></form></div></article><?php endforeach; ?></div><?php endif; ?>
</section>
<dialog class="modal" id="question-modal" data-modal <?= $editQuestion || $errors ? 'open' : '' ?>><div class="modal-card large"><div class="modal-head"><div><span class="eyebrow">Lesson assessment</span><h2><?= $editQuestion ? 'Edit question' : 'Create question' ?></h2></div><a class="icon-button" href="<?= e(url('admin/questions.php?lesson=' . $lessonId)) ?>" aria-label="Close form"><?= icon('x') ?></a></div><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="question_id" value="<?= (int) ($editQuestion['id'] ?? 0) ?>"><div class="field"><label for="question">Question</label><textarea id="question" name="question" rows="3" required><?= e($_POST['question'] ?? $editQuestion['question'] ?? '') ?></textarea></div><div class="form-grid two"><?php foreach (['a','b','c','d'] as $letter): ?><div class="field"><label for="option-<?= $letter ?>">Option <?= strtoupper($letter) ?></label><input id="option-<?= $letter ?>" name="option_<?= $letter ?>" value="<?= e($_POST['option_' . $letter] ?? $editQuestion['option_' . $letter] ?? '') ?>" required></div><?php endforeach; ?></div><div class="form-grid two"><div class="field"><label for="correct-option">Correct answer</label><select id="correct-option" name="correct_option" required><option value="">Select answer</option><?php $correct = strtoupper((string) ($_POST['correct_option'] ?? $editQuestion['correct_option'] ?? '')); foreach (['A','B','C','D'] as $letter): ?><option value="<?= $letter ?>" <?= $correct === $letter ? 'selected' : '' ?>>Option <?= $letter ?></option><?php endforeach; ?></select></div><div class="field"><label for="question-order">Sort order</label><input id="question-order" name="sort_order" type="number" value="<?= e((string) ($_POST['sort_order'] ?? $editQuestion['sort_order'] ?? (count($questions) + 1) * 10)) ?>" required></div></div><div class="field"><label for="explanation">Learner explanation</label><textarea id="explanation" name="explanation" rows="4" required><?= e($_POST['explanation'] ?? $editQuestion['explanation'] ?? '') ?></textarea><small class="field-hint">Explain why the answer is correct rather than only repeating it.</small></div><div class="modal-actions"><a class="button button-secondary" href="<?= e(url('admin/questions.php?lesson=' . $lessonId)) ?>">Cancel</a><button class="button" type="submit">Save question</button></div></form></div></dialog>
<?php require base_path('partials/footer.php'); ?>

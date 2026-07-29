<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$lessonId = request_int('lesson');
$lesson = Learning::lesson($lessonId);
if (!$lesson) { http_response_code(404); exit('Lesson not found.'); }
$questions = Learning::questions($lessonId);
$result = null;
if (is_post()) {
    verify_csrf();
    $answers = is_array($_POST['answer'] ?? null) ? $_POST['answer'] : [];
    $result = Learning::submitQuiz((int) current_user()['id'], $lessonId, $answers);
}
$pageTitle = 'Quiz: ' . $lesson['title'];
$bodyClass = 'app-page quiz-page';
require base_path('partials/header.php');
?>
<section class="page-hero quiz-hero"><div class="container"><a class="back-link" href="<?= e(url('lesson.php?id=' . $lessonId)) ?>">← Return to lesson</a><span class="eyebrow light">Knowledge check</span><h1><?= e($lesson['title']) ?> quiz</h1><p>Choose the best answer for each question. You need 60% to complete the lesson.</p></div></section>
<section class="section">
    <div class="container quiz-container">
        <?php if ($result): ?>
        <section class="quiz-result <?= $result['passed'] ? 'passed' : 'retry' ?>">
            <div class="result-icon" aria-hidden="true"><?= $result['passed'] ? '🎉' : '🌱' ?></div>
            <div><span class="eyebrow"><?= $result['passed'] ? 'Lesson completed' : 'Keep practising' ?></span><h2><?= $result['score'] ?>%</h2><p>You answered <?= $result['correct'] ?> of <?= $result['total'] ?> questions correctly. <?= $result['passed'] ? 'Your result and learning points have been saved.' : 'Review the explanations and try again when ready.' ?></p></div>
            <div class="result-actions"><a class="button" href="<?= e(url($result['passed'] ? 'courses.php' : 'lesson.php?id=' . $lessonId)) ?>"><?= $result['passed'] ? 'Continue learning' : 'Review lesson' ?></a><a class="button button-secondary" href="<?= e(url('quiz.php?lesson=' . $lessonId)) ?>">Try again</a></div>
        </section>
        <?php endif; ?>
        <form method="post" class="quiz-form">
            <?= csrf_field() ?>
            <?php foreach ($questions as $index => $question): ?>
            <fieldset class="question-card">
                <legend><span><?= $index + 1 ?></span><?= e($question['question']) ?></legend>
                <?php foreach (['A', 'B', 'C', 'D'] as $option): $key = 'option_' . strtolower($option); ?>
                <label class="quiz-option <?= $result ? (($result['feedback'][$question['id']]['correct_option'] === $option) ? 'correct-option' : (($result['feedback'][$question['id']]['selected'] === $option) ? 'wrong-option' : '')) : '' ?>">
                    <input type="radio" name="answer[<?= (int) $question['id'] ?>]" value="<?= $option ?>" <?= $result ? 'disabled' : 'required' ?>><span class="option-letter"><?= $option ?></span><span><?= e($question[$key]) ?></span>
                </label>
                <?php endforeach; ?>
                <?php if ($result): ?><div class="answer-explanation <?= $result['feedback'][$question['id']]['correct'] ? 'correct' : 'incorrect' ?>"><strong><?= $result['feedback'][$question['id']]['correct'] ? 'Correct' : 'Correct answer: ' . e($result['feedback'][$question['id']]['correct_option']) ?></strong><p><?= e($result['feedback'][$question['id']]['explanation']) ?></p></div><?php endif; ?>
            </fieldset>
            <?php endforeach; ?>
            <?php if (!$result): ?><button class="button button-large button-full" type="submit">Check my answers</button><?php endif; ?>
        </form>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>

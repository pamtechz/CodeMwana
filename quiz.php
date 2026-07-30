<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user=current_user();
$identifier=(string)($_GET['lesson']??request_int('id'));
$lesson=Learning::lesson($identifier);
if(!$lesson){flash('error','The selected lesson is not available.');redirect('courses.php');}
$questions=Learning::questions((int)$lesson['id']);
if(!$questions){flash('error','This lesson does not have a published quiz.');redirect('lesson.php?lesson='.urlencode($lesson['slug']));}
$result=null;$errors=[];
if(is_post()){
 verify_csrf();
 $answers=$_POST['answers']??[];
 if(!is_array($answers))$answers=[];
 if(count(array_filter($answers,fn($value)=>in_array(strtoupper((string)$value),['A','B','C','D'],true)))<count($questions))$errors['quiz']='Answer every question before submitting the assessment.';
 if(!$errors)$result=Learning::submitQuiz((int)$user['id'],(int)$lesson['id'],$answers);
}
$pageTitle='Quiz: '.$lesson['title'];$bodyClass='quiz-page';require base_path('partials/header.php');
?>
<section class="quiz-header"><div><a class="back-link" href="<?= e(url('lesson.php?lesson='.urlencode($lesson['slug']))) ?>"><?= icon('arrow-left') ?>Return to lesson</a><span class="eyebrow"><?= e($lesson['course_title']) ?></span><h1><?= e($lesson['title']) ?> assessment</h1><p>Answer every question. Your score and explanations appear immediately after submission.</p></div><div class="quiz-summary"><span><?= icon('check-circle') ?></span><div><strong><?= count($questions) ?> questions</strong><small>60% required to complete lesson</small></div></div></section>
<?php if($result): ?>
<section class="quiz-result-panel <?= $result['passed']?'passed':'retry' ?>"><div class="result-score"><span><?= $result['score'] ?>%</span><small><?= $result['passed']?'Lesson completed':'More practice needed' ?></small></div><div><span class="eyebrow"><?= $result['passed']?'Assessment passed':'Assessment submitted' ?></span><h2><?= $result['passed']?'Well done — your progress has been saved.':'Review the explanations and try again.' ?></h2><p>You answered <?= $result['correct'] ?> of <?= $result['total'] ?> questions correctly.</p></div><div class="result-actions"><a class="button" href="<?= e(url('course.php?course='.urlencode($lesson['course_slug']))) ?>">Return to path</a><a class="button button-secondary" href="<?= e(url('quiz.php?lesson='.urlencode($lesson['slug']))) ?>">Attempt again</a></div></section>
<?php endif; ?>
<?php if(isset($errors['quiz'])): ?><div class="alert alert-danger"><?= icon('alert-circle') ?><span><?= e($errors['quiz']) ?></span></div><?php endif; ?>
<form method="post" class="quiz-form" data-quiz-form>
<?= csrf_field() ?>
<div class="quiz-progress-bar"><span data-quiz-progress>0 of <?= count($questions) ?> answered</span><div class="progress-track"><i data-quiz-progress-bar></i></div></div>
<?php foreach($questions as $index=>$question): $feedback=$result['feedback'][(int)$question['id']]??null; ?>
<fieldset class="quiz-question <?= $feedback?($feedback['correct']?'correct':'incorrect'):'' ?>"><legend><span><?= str_pad((string)($index+1),2,'0',STR_PAD_LEFT) ?></span><?= e($question['question']) ?></legend><div class="quiz-options"><?php foreach(['A','B','C','D'] as $option): $key='option_'.strtolower($option);$selected=(string)($_POST['answers'][$question['id']]??'')===$option; ?><label class="quiz-option"><input type="radio" name="answers[<?= (int)$question['id'] ?>]" value="<?= $option ?>" <?= $selected?'checked':'' ?> <?= $result?'disabled':'' ?>><span class="option-letter"><?= $option ?></span><span><?= e($question[$key]) ?></span><?php if($feedback&&$feedback['correct_option']===$option): ?><i><?= icon('check') ?></i><?php endif; ?></label><?php endforeach; ?></div><?php if($feedback): ?><div class="question-feedback <?= $feedback['correct']?'success':'danger' ?>"><?= icon($feedback['correct']?'check-circle':'alert-circle') ?><div><strong><?= $feedback['correct']?'Correct answer':'Correct answer: '.$feedback['correct_option'] ?></strong><p><?= e($feedback['explanation']) ?></p></div></div><?php endif; ?></fieldset>
<?php endforeach; ?>
<?php if(!$result): ?><div class="quiz-submit"><div><strong>Ready to submit?</strong><small>Your highest score is retained in your progress record.</small></div><button class="button button-large" type="submit" data-submit-button>Submit assessment<?= icon('arrow-right') ?></button></div><?php endif; ?>
</form>
<?php require base_path('partials/footer.php'); ?>

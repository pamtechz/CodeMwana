<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user=current_user();
$identifier=(string)($_GET['course']??'');
$course=Learning::course($identifier);
if(!$course){http_response_code(404);$pageTitle='Path not found';require base_path('partials/header.php');echo '<section class="state-page"><div class="state-card"><span class="state-icon">404</span><h1>Learning path not found</h1><p>The requested path is not published or no longer exists.</p><a class="button" href="'.e(url('courses.php')).'">Return to learning paths</a></div></section>';require base_path('partials/footer.php');exit;}
if(is_post()){verify_csrf();Learning::enroll((int)$user['id'],(int)$course['id']);flash('success','You are enrolled. Begin with the first incomplete lesson.');redirect('course.php?course='.urlencode($course['slug']));}
$lessons=Learning::lessonsForCourse((int)$course['id'],(int)$user['id']);
$enrolled=(bool)Database::fetch("SELECT id FROM course_enrollments WHERE user_id=? AND course_id=? AND status='active'",[$user['id'],$course['id']]);
$completed=count(array_filter($lessons,fn($l)=>$l['progress_status']==='completed'));
$percent=count($lessons)?(int)round($completed/count($lessons)*100):0;
$outcomes=array_filter(array_map('trim',explode("\n",$course['outcomes'])));
$pageTitle=$course['title'];$bodyClass='course-detail-page';require base_path('partials/header.php');
?>
<section class="course-hero" style="--course:<?= e($course['colour']) ?>"><div class="course-hero-copy"><a class="back-link" href="<?= e(url('courses.php')) ?>"><?= icon('arrow-left') ?>All learning paths</a><div class="course-hero-title"><span class="course-icon xlarge"><?= icon($course['icon']) ?></span><div><div class="inline-meta light"><span><?= e($course['level']) ?></span><span><?= e($course['audience']) ?></span><span><?= e($course['estimated_time']) ?></span></div><h1><?= e($course['title']) ?></h1><p><?= e($course['description']) ?></p></div></div></div><div class="course-hero-card"><div class="progress-ring large" style="--progress:<?= $percent ?>"><strong><?= $percent ?>%</strong><span>complete</span></div><div><strong><?= $completed ?> of <?= count($lessons) ?> lessons</strong><small><?= $enrolled?'Your progress is saved':'Enrol to add this path to your dashboard' ?></small></div><?php if(!$enrolled): ?><form method="post"><?= csrf_field() ?><button class="button button-light button-full" type="submit">Enrol in this path</button></form><?php endif; ?></div></section>
<div class="course-detail-grid">
<main class="panel lesson-plan-panel"><div class="panel-heading"><div><span class="eyebrow">Course plan</span><h2>Lessons in this path</h2></div><span class="count-badge"><?= count($lessons) ?> lessons</span></div><div class="lesson-plan-list"><?php foreach($lessons as $index=>$lesson): ?><a class="lesson-plan-row <?= e($lesson['progress_status']) ?>" href="<?= e(url('lesson.php?lesson='.urlencode($lesson['slug']))) ?>"><span class="lesson-sequence"><?php if($lesson['progress_status']==='completed'): ?><?= icon('check') ?><?php else: ?><?= str_pad((string)($index+1),2,'0',STR_PAD_LEFT) ?><?php endif; ?></span><span class="lesson-plan-info"><small><?= e(ucfirst(str_replace('_',' ',$lesson['progress_status']))) ?></small><strong><?= e($lesson['title']) ?></strong><span><?= e($lesson['summary']) ?></span><span class="inline-meta"><i><?= icon('clock') ?><?= (int)$lesson['duration_minutes'] ?> min</i><i><?= icon('check-circle') ?>Best score <?= (int)$lesson['best_score'] ?>%</i></span></span><?= icon('arrow-right') ?></a><?php endforeach; ?></div></main>
<aside class="course-sidebar"><section class="panel"><span class="eyebrow">Learning outcomes</span><h2>By the end, you can</h2><ul class="outcome-list"><?php foreach($outcomes as $outcome): ?><li><?= icon('check-circle') ?><span><?= e($outcome) ?></span></li><?php endforeach; ?></ul></section><section class="panel"><span class="eyebrow">How assessment works</span><h2>Complete each lesson</h2><p>Read the lesson, attempt the practical challenge and score at least 60% on the quiz. Your highest score is retained.</p></section></aside>
</div>
<?php require base_path('partials/footer.php'); ?>

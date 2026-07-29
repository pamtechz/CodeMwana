<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$leaders = Learning::leaderboard(50);
$userId = (int) current_user()['id'];
$pageTitle = 'Leaderboard';
$bodyClass = 'app-page';
require base_path('partials/header.php');
?>
<section class="page-hero leaderboard-hero"><div class="container"><span class="eyebrow light">Celebrate learning effort</span><h1>CodeMwana leaderboard</h1><p>Points are earned by completing lessons, passing quizzes, saving projects and unlocking badges.</p></div></section>
<section class="section"><div class="container leaderboard-container">
    <?php if (count($leaders) >= 3): ?><div class="podium"><div class="podium-place second"><span class="avatar large"><?= e(strtoupper(substr($leaders[1]['name'],0,1))) ?></span><strong><?= e($leaders[1]['name']) ?></strong><small><?= (int) $leaders[1]['points'] ?> points</small><div>2</div></div><div class="podium-place first"><span class="crown" aria-hidden="true">👑</span><span class="avatar large"><?= e(strtoupper(substr($leaders[0]['name'],0,1))) ?></span><strong><?= e($leaders[0]['name']) ?></strong><small><?= (int) $leaders[0]['points'] ?> points</small><div>1</div></div><div class="podium-place third"><span class="avatar large"><?= e(strtoupper(substr($leaders[2]['name'],0,1))) ?></span><strong><?= e($leaders[2]['name']) ?></strong><small><?= (int) $leaders[2]['points'] ?> points</small><div>3</div></div></div><?php endif; ?>
    <div class="leaderboard-card"><div class="leaderboard-heading"><h2>All learners</h2><span><?= count($leaders) ?> active accounts</span></div><?php foreach ($leaders as $index => $leader): ?><div class="leader-row <?= (int) $leader['id'] === $userId ? 'current-user' : '' ?>"><span class="rank"><?= $index + 1 ?></span><span class="avatar"><?= e(strtoupper(substr($leader['name'],0,1))) ?></span><span class="leader-name"><strong><?= e($leader['name']) ?><?= (int) $leader['id'] === $userId ? ' (You)' : '' ?></strong><small>@<?= e($leader['username']) ?></small></span><span class="streak">🔥 <?= (int) $leader['streak_days'] ?> days</span><strong class="leader-points"><?= number_format((int) $leader['points']) ?> pts</strong></div><?php endforeach; ?></div>
    <div class="fair-play-note"><span aria-hidden="true">💡</span><div><strong>Learning comes before ranking</strong><p>The leaderboard is optional motivation. A learner's most important comparison is with their own previous work.</p></div></div>
</div></section>
<?php require base_path('partials/footer.php'); ?>

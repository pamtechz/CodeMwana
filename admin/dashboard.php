<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');
$counts = [
    'users' => (int)(Database::fetch('SELECT COUNT(*) AS total FROM users')['total'] ?? 0),
    'courses' => (int)(Database::fetch('SELECT COUNT(*) AS total FROM courses')['total'] ?? 0),
    'lessons' => (int)(Database::fetch('SELECT COUNT(*) AS total FROM lessons')['total'] ?? 0),
    'projects' => (int)(Database::fetch('SELECT COUNT(*) AS total FROM projects')['total'] ?? 0),
];
$users = Database::fetchAll('SELECT id, name, username, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 20');
$courses = Database::fetchAll('SELECT c.*, COUNT(l.id) AS lesson_count FROM courses c LEFT JOIN lessons l ON l.course_id = c.id GROUP BY c.id ORDER BY c.sort_order');
$pageTitle = 'Administration'; $bodyClass = 'app-page admin-page'; require base_path('partials/header.php');
?>
<section class="page-hero admin-hero"><div class="container"><span class="eyebrow light">System administration</span><h1>CodeMwana management</h1><p>Review platform content, user accounts and database activity.</p></div></section>
<section class="section"><div class="container">
<div class="dashboard-stat-grid four"><article class="metric-card"><span class="metric-icon purple">👥</span><div><strong><?=$counts['users']?></strong><small>User accounts</small></div></article><article class="metric-card"><span class="metric-icon orange">🗺️</span><div><strong><?=$counts['courses']?></strong><small>Learning paths</small></div></article><article class="metric-card"><span class="metric-icon green">📘</span><div><strong><?=$counts['lessons']?></strong><small>Lessons</small></div></article><article class="metric-card"><span class="metric-icon blue">{ }</span><div><strong><?=$counts['projects']?></strong><small>Saved projects</small></div></article></div>
<div class="admin-grid"><section><div class="section-row"><div><span class="eyebrow">Latest accounts</span><h2>User management</h2></div></div><div class="table-card"><div class="responsive-table"><table><thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach($users as $account):?><tr><td><strong><?=e($account['name'])?></strong><small class="table-subtext"><?=e($account['email'])?></small></td><td><span class="role-pill"><?=e(ucfirst($account['role']))?></span></td><td><span class="status-pill <?=$account['status']==='active'?'success':'warning'?>"><?=e(ucfirst($account['status']))?></span></td><td><?=e(date('j M Y',strtotime($account['created_at'])))?></td></tr><?php endforeach;?></tbody></table></div></div></section><aside><div class="sidebar-card"><span class="eyebrow">Content inventory</span><h2>Learning paths</h2><div class="admin-course-list"><?php foreach($courses as $course):?><div><span aria-hidden="true"><?=e($course['icon'])?></span><div><strong><?=e($course['title'])?></strong><small><?= (int)$course['lesson_count'] ?> lessons · <?= (int)$course['is_published']?'Published':'Draft' ?></small></div></div><?php endforeach;?></div></div><div class="sidebar-card"><h2>Deployment checks</h2><ul class="check-list"><li>Change seeded demonstration passwords.</li><li>Disable or remove setup.php after installation.</li><li>Use HTTPS on the hosted domain.</li><li>Back up the MySQL database regularly.</li></ul></div></aside></div>
</div></section>
<?php require base_path('partials/footer.php'); ?>

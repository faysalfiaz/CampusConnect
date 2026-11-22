<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$notices = rows('SELECT * FROM notices ORDER BY created_at DESC LIMIT 50');
$portal = 'student'; $title = 'Notice Board';
require __DIR__ . '/../includes/header.php';
foreach ($notices as $n): ?>
<div class="card mb-3 <?= $n['is_emergency'] ? 'border-danger' : '' ?>"><div class="card-body"><h6><?= $n['is_emergency'] ? '<span class="badge bg-danger">EMERGENCY</span> ' : '' ?><?= e($n['title']) ?></h6><p class="mb-1" style="white-space:pre-line"><?= e($n['body']) ?></p><div class="small text-muted"><?= dt($n['created_at']) ?></div></div></div>
<?php endforeach; if (!$notices) echo '<div class="empty"><i class="bi bi-megaphone"></i>No notices.</div>';
require __DIR__ . '/../includes/footer.php';

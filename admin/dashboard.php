<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$n = fn($sql) => (int) val($sql);
$stats = [
 ['Students', $n("SELECT COUNT(*) FROM users WHERE role='student'"), 'mortarboard'], ['Clubs', $n('SELECT COUNT(*) FROM clubs'), 'building'],
 ['Total posts', $n('SELECT COUNT(*) FROM posts'), 'file-earmark-text'], ['Pending posts', $n("SELECT COUNT(*) FROM posts WHERE status='pending'"), 'hourglass-split'],
 ['Published posts', $n("SELECT COUNT(*) FROM posts WHERE status='published'"), 'check-circle'], ['Rejected posts', $n("SELECT COUNT(*) FROM posts WHERE status='rejected'"), 'x-circle'],
 ['Total events', $n('SELECT COUNT(*) FROM events'), 'calendar-event'], ['Pending events', $n("SELECT COUNT(*) FROM events WHERE status='pending'"), 'calendar-check'],
 ['Registrations', $n("SELECT COUNT(*) FROM event_registrations WHERE status='registered'"), 'journal-check'], ['Open reports', $n("SELECT COUNT(*) FROM post_reports WHERE status='open'"), 'flag'],
];
$recent = rows('SELECT l.*, u.email FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 10');
$byStatus = rows('SELECT status, COUNT(*) c FROM posts GROUP BY status');
$noHead = true; $portal = 'admin'; $title = 'Admin Dashboard';
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>
new Chart(document.getElementById("c1"),{type:"doughnut",data:{labels:' . json_encode(array_column($byStatus, 'status')) . ',datasets:[{data:' . json_encode(array_map('intval', array_column($byStatus, 'c'))) . ',backgroundColor:["#94a3b8","#f59e0b","#22c55e","#ef4444","#16a34a","#475569"]}]}});</script>';
require __DIR__ . '/../includes/header.php'; ?><section class="dash-hero"><div class="dh-text"><span class="dh-chip"><i class="bi bi-stars"></i> Admin portal</span><h2>Campus overview</h2><p>Review what is waiting and keep everything running smoothly.</p><div class="dh-actions"><a class="btn btn-light" href="<?= url('admin/posts.php') ?>"><i class="bi bi-check2-square"></i> Review posts</a><a class="btn btn-outline-light" href="<?= url('admin/events.php') ?>"><i class="bi bi-calendar-check"></i> Review events</a><a class="btn btn-outline-light" href="<?= url('admin/analytics.php') ?>"><i class="bi bi-graph-up"></i> Analytics</a></div></div><div class="dh-date"><b><?= date('d') ?></b><span><?= date('M Y') ?></span><small><?= date('l') ?></small></div></section><?php  ?>
<div class="row g-3 mb-4"><?php foreach ($stats as [$l, $v, $i]): ?><div class="col-6 col-lg-3 col-xl-2"><div class="card stat-card shadow-sm"><div class="card-body"><i class="bi bi-<?= $i ?> text-accent"></i> <span class="text-muted small"><?= $l ?></span><div class="num"><?= $v ?></div></div></div></div><?php endforeach; ?></div>
<div class="row g-3"><div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Posts by status</div><div class="card-body"><canvas id="c1"></canvas></div></div></div>
<div class="col-lg-8"><div class="card shadow-sm"><div class="card-header">Recent activity</div><ul class="list-group list-group-flush small">
<?php foreach ($recent as $r): ?><li class="list-group-item"><strong><?= e(str_replace('_', ' ', $r['action'])) ?></strong> <span class="text-muted"><?= e($r['email'] ?? '') ?> <?= e($r['role'] ? '(' . $r['role'] . ')' : '') ?> <?= e($r['details']) ?> · <?= dt($r['created_at']) ?></span></li><?php endforeach; ?></ul></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

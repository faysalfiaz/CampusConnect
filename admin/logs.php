<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$qs = get('q');
$logs = rows('SELECT l.*, u.email FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id WHERE l.action LIKE ? OR l.details LIKE ? OR u.email LIKE ? ORDER BY l.created_at DESC LIMIT 300', array_fill(0, 3, "%$qs%"));
$portal = 'admin'; $title = 'Activity Logs';
require __DIR__ . '/../includes/header.php'; ?>
<form class="d-flex gap-2 mb-3"><input name="q" class="form-control" placeholder="Filter by action, detail or email" value="<?= e($qs) ?>"><button class="btn btn-accent">Filter</button></form>
<div class="table-responsive"><table class="table table-sm small"><thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= dt($l['created_at']) ?></td><td><?= e($l['email']) ?></td><td><?= e($l['role']) ?></td><td><?= e($l['action']) ?></td><td><?= e($l['details']) ?></td><td><?= e($l['ip']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php';

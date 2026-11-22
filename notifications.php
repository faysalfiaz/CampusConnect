<?php
require_once __DIR__ . '/includes/auth.php'; require_login();
$u = user();
if (is_post()) { csrf_check();
    if (post('action') === 'read_all') q('UPDATE notifications SET is_read=1 WHERE user_id=?', [$u['id']]);
    if (post('action') === 'read') q('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?', [(int) post('id'), $u['id']]);
    redirect('notifications.php');
}
$items = rows('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100', [$u['id']]);
$portal = $u['role']; $title = 'Notifications';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="mb-3"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-sm btn-outline-secondary">Mark all as read</button></form>
<?php if (!$items): ?><div class="empty"><i class="bi bi-bell-slash"></i>No notifications yet.</div><?php endif; ?>
<div class="list-group">
<?php foreach ($items as $n): ?>
  <div class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'list-group-item-info' ?>">
    <div><div class="fw-semibold"><?= e($n['title']) ?></div><div class="small"><?= e($n['body']) ?></div>
      <div class="small text-muted"><?= dt($n['created_at']) ?><?= $n['link'] ? ' · <a href="' . e(url($n['link'])) . '">Open</a>' : '' ?></div></div>
    <?php if (!$n['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int) $n['id'] ?>"><button class="btn btn-sm btn-link" data-no-loading="1">Mark read</button></form><?php endif; ?>
  </div>
<?php endforeach; ?></div>
<?php require __DIR__ . '/includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
if (is_post()) {
    csrf_check(); $cid = (int) post('comment_id'); $a = post('action');
    if ($a === 'hide') { q("UPDATE post_comments SET status='hidden' WHERE id=?", [$cid]); q("UPDATE post_reports SET status='resolved' WHERE comment_id=?", [$cid]); log_activity('comment_hidden', '#' . $cid); flash('success', 'Comment hidden.'); }
    elseif ($a === 'delete') { q('DELETE FROM post_comments WHERE id=?', [$cid]); log_activity('comment_deleted', '#' . $cid); flash('success', 'Comment deleted.'); }
    elseif ($a === 'restore') { q("UPDATE post_comments SET status='visible' WHERE id=?", [$cid]); flash('success', 'Comment restored.'); }
    elseif ($a === 'dismiss') { q("UPDATE post_reports SET status='dismissed' WHERE comment_id=?", [$cid]); flash('success', 'Reports dismissed.'); }
    redirect('admin/comments.php?view=' . urlencode(get('view', 'reports')));
}
$view = get('view', 'reports');
if ($view === 'reports') $items = rows("SELECT c.*, s.full_name, p.title ptitle, (SELECT COUNT(*) FROM post_reports r WHERE r.comment_id=c.id AND r.status='open') reports, (SELECT GROUP_CONCAT(r.reason SEPARATOR ' | ') FROM post_reports r WHERE r.comment_id=c.id AND r.status='open') reasons FROM post_comments c JOIN students s ON s.user_id=c.user_id JOIN posts p ON p.id=c.post_id WHERE EXISTS (SELECT 1 FROM post_reports r WHERE r.comment_id=c.id AND r.status='open') ORDER BY c.created_at DESC");
else $items = rows("SELECT c.*, s.full_name, p.title ptitle, 0 reports, '' reasons FROM post_comments c JOIN students s ON s.user_id=c.user_id JOIN posts p ON p.id=c.post_id ORDER BY c.created_at DESC LIMIT 100");
$portal = 'admin'; $title = 'Comment Moderation';
require __DIR__ . '/../includes/header.php'; ?>
<ul class="nav nav-pills mb-3"><li class="nav-item"><a class="nav-link <?= $view === 'reports' ? 'active' : '' ?>" href="?view=reports">Reported</a></li><li class="nav-item"><a class="nav-link <?= $view === 'all' ? 'active' : '' ?>" href="?view=all">All recent</a></li></ul>
<?php if (!$items): ?><div class="empty"><i class="bi bi-chat-dots"></i>Nothing to moderate.</div><?php endif; ?>
<?php foreach ($items as $c): ?><div class="card mb-2"><div class="card-body"><div class="small text-muted"><?= e($c['full_name']) ?> on “<?= e($c['ptitle']) ?>” · <?= dt($c['created_at']) ?> <?= $c['status'] === 'hidden' ? '<span class="badge bg-secondary">hidden</span>' : '' ?></div>
<p class="mb-1"><?= e($c['body']) ?></p><?php if ($c['reports']): ?><div class="small text-danger">⚑ <?= (int) $c['reports'] ?> report(s): <?= e($c['reasons']) ?></div><?php endif; ?>
<form method="post" class="mt-2 d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>">
<?php if ($c['status'] === 'visible'): ?><button name="action" value="hide" class="btn btn-sm btn-warning">Hide</button><?php else: ?><button name="action" value="restore" class="btn btn-sm btn-outline-secondary">Restore</button><?php endif; ?>
<?php if ($c['reports']): ?><button name="action" value="dismiss" class="btn btn-sm btn-outline-secondary">Dismiss reports</button><?php endif; ?>
<button name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete permanently?')">Delete</button></form></div></div><?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php';

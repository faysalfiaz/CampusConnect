<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $uid = user()['id'];
if (is_post()) {
    csrf_check(); $pid = (int) post('id'); $a = post('action');
    $p = row('SELECT * FROM posts WHERE id=? AND club_id=?', [$pid, $cid]);
    if ($p) {
        if ($a === 'submit' && in_array($p['status'], ['draft', 'rejected'], true)) {
            q("UPDATE posts SET status='pending', rejection_reason=NULL WHERE id=?", [$pid]);
            q('INSERT INTO post_approvals (post_id, action, actor_user_id) VALUES (?,?,?)', [$pid, $p['status'] === 'rejected' ? 'resubmitted' : 'submitted', $uid]);
            notify_admins('Post ' . ($p['status'] === 'rejected' ? 'resubmitted' : 'submitted'), $c['name'] . ': ' . $p['title'], 'admin/post-review.php?id=' . $pid);
            flash('success', 'Submitted for approval.');
        } elseif ($a === 'archive' && $p['status'] === 'published') {
            q("UPDATE posts SET status='archived' WHERE id=?", [$pid]); q("INSERT INTO post_approvals (post_id, action, actor_user_id) VALUES (?, 'archived', ?)", [$pid, $uid]); flash('success', 'Post archived (removed from feed).');
        } elseif ($a === 'delete' && in_array($p['status'], ['draft', 'rejected', 'archived'], true)) {
            q('DELETE FROM posts WHERE id=?', [$pid]); flash('success', 'Post deleted.');
        } else flash('error', 'That action is not allowed for this post.');
        log_activity('post_' . $a, $p['title']);
    }
    redirect('club/posts.php?status=' . urlencode(get('status')));
}
$st = get('status'); $valid = ['draft', 'pending', 'rejected', 'published', 'archived'];
$posts = rows('SELECT p.*, (SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id) likes, (SELECT COUNT(*) FROM post_comments x WHERE x.post_id=p.id) comments FROM posts p WHERE p.club_id=?' . (in_array($st, $valid, true) ? ' AND p.status=?' : '') . ' ORDER BY p.updated_at DESC', in_array($st, $valid, true) ? [$cid, $st] : [$cid]);
$portal = 'club'; $title = 'Posts';
require __DIR__ . '/../includes/header.php'; ?>
<ul class="nav nav-pills mb-3"><li class="nav-item"><a class="nav-link <?= $st === '' ? 'active' : '' ?>" href="?">All</a></li><?php foreach ($valid as $s): ?><li class="nav-item"><a class="nav-link <?= $st === $s ? 'active' : '' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?></a></li><?php endforeach; ?></ul>
<?php if (!$posts): ?><div class="empty"><i class="bi bi-file-earmark-text"></i>No posts here. <a href="<?= url('club/create-post.php') ?>">Create one</a></div><?php endif; ?>
<?php foreach ($posts as $p): ?>
<div class="card mb-2 shadow-sm"><div class="card-body"><div class="d-flex justify-content-between flex-wrap gap-2">
 <div><strong><?= e($p['title']) ?></strong> <?= status_badge($p['status']) ?><div class="small text-muted">Updated <?= dt($p['updated_at']) ?> · v<?= (int) $p['version'] ?> · ♥ <?= (int) $p['likes'] ?> · 💬 <?= (int) $p['comments'] ?></div>
 <?php if ($p['status'] === 'rejected'): ?><div class="alert alert-danger py-1 px-2 mt-2 mb-0 small"><strong>Admin feedback:</strong> <?= e($p['rejection_reason']) ?></div><?php endif; ?></div>
 <div class="d-flex gap-1 align-items-start flex-wrap">
  <?php if ($p['status'] !== 'archived'): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('club/create-post.php?id=' . $p['id']) ?>">Edit</a><?php endif; ?>
  <?php foreach ([['submit', ['draft', 'rejected'], 'Submit', 'accent'], ['archive', ['published'], 'Archive', 'outline-dark'], ['delete', ['draft', 'rejected', 'archived'], 'Delete', 'outline-danger']] as [$act, $sts, $lab, $cls]):
    if (in_array($p['status'], $sts, true)): ?>
   <form method="post" <?= $act !== 'submit' ? 'data-confirm="Are you sure?"' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>"><button class="btn btn-sm btn-<?= $cls ?>"><?= $p['status'] === 'rejected' && $act === 'submit' ? 'Resubmit' : $lab ?></button></form>
  <?php endif; endforeach; ?></div></div></div></div>
<?php endforeach; require __DIR__ . '/../includes/footer.php';

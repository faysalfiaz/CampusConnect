<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$id = (int) get('id'); $aid = user()['id'];
$p = row("SELECT p.*, c.name club_name, c.user_id club_user, c.official_email, c.logo FROM posts p JOIN clubs c ON c.id=p.club_id WHERE p.id=? AND p.status<>'draft'", [$id]);
if (!$p) { http_response_code(404); exit('Post not found (drafts are private to clubs).'); }
if (is_post()) {
    csrf_check(); $a = post('action'); $note = post('internal_note');
    if ($a === 'approve' && $p['status'] === 'pending') {
        q("UPDATE posts SET status='published', published_at=NOW(), rejection_reason=NULL WHERE id=?", [$id]);
        q("INSERT INTO post_approvals (post_id, action, actor_user_id, internal_note) VALUES (?, 'approved', ?, ?)", [$id, $aid, $note ?: null]);
        notify((int) $p['club_user'], 'Post approved', '"' . $p['title'] . '" is now live on the student feed.', 'club/posts.php?status=published');
        notify_all_students('New post from ' . $p['club_name'], $p['title'], 'student/feed.php');
        log_activity('post_approved', $p['title']); flash('success', 'Post approved and published.');
    } elseif ($a === 'reject' && $p['status'] === 'pending') {
        $reason = post('reason');
        if (mb_strlen($reason) < 10) { flash('error', 'A clear rejection reason (at least 10 characters) is mandatory.'); redirect('admin/post-review.php?id=' . $id); }
        q("UPDATE posts SET status='rejected', rejection_reason=? WHERE id=?", [$reason, $id]);
        q("INSERT INTO post_approvals (post_id, action, actor_user_id, reason, internal_note) VALUES (?, 'rejected', ?, ?, ?)", [$id, $aid, $reason, $note ?: null]);
        notify((int) $p['club_user'], 'Post rejected', '"' . $p['title'] . '" was rejected. Reason: ' . mb_strimwidth($reason, 0, 300, '…'), 'club/create-post.php?id=' . $id);
        log_activity('post_rejected', $p['title']); flash('success', 'Post rejected. The reason is visible only to that club.');
    } elseif ($a === 'unpublish' && $p['status'] === 'published') {
        $reason = post('reason'); if (mb_strlen($reason) < 10) { flash('error', 'Give a reason (10+ characters).'); redirect('admin/post-review.php?id=' . $id); }
        q("UPDATE posts SET status='rejected', rejection_reason=? WHERE id=?", [$reason, $id]);
        q("INSERT INTO post_approvals (post_id, action, actor_user_id, reason) VALUES (?, 'rejected', ?, ?)", [$id, $aid, $reason]);
        notify((int) $p['club_user'], 'Post removed by admin', '"' . $p['title'] . '": ' . $reason, 'club/create-post.php?id=' . $id); log_activity('post_unpublished', $p['title']); flash('success', 'Post removed from the feed.');
    }
    redirect('admin/post-review.php?id=' . $id);
}
$hist = rows('SELECT a.*, u.email FROM post_approvals a JOIN users u ON u.id=a.actor_user_id WHERE a.post_id=? ORDER BY a.created_at DESC', [$id]);
$portal = 'admin'; $title = 'Review: ' . $p['title'];
require __DIR__ . '/../includes/header.php'; ?>
<div class="row g-4"><div class="col-lg-7"><div class="card shadow-sm"><div class="card-body">
 <p><?= status_badge($p['status']) ?> v<?= (int) $p['version'] ?> · submitted <?= dt($p['updated_at']) ?></p>
 <h5><?= e($p['title']) ?></h5><p style="white-space:pre-line"><?= e($p['description']) ?></p>
 <?php if ($p['event_date'] || $p['event_venue']): ?><p class="small text-muted"><i class="bi bi-calendar-event"></i> <?= e($p['event_date']) ?> <?= e($p['event_time']) ?> · <?= e($p['event_venue']) ?></p><?php endif; ?>
 <?php if ($p['extra_info']): ?><p class="small"><?= e($p['extra_info']) ?></p><?php endif; ?>
 <?php if ($p['image']): ?><img src="<?= url($p['image']) ?>" class="img-fluid rounded" alt=""><?php endif; ?>
 <?php if ($p['attachment']): ?><p class="mt-2"><a href="<?= url($p['attachment']) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> Attachment</a></p><?php endif; ?></div></div></div>
<div class="col-lg-5">
 <div class="card shadow-sm mb-3"><div class="card-body small"><strong>Club:</strong> <?= e($p['club_name']) ?><br><strong>Official email:</strong> <?= e($p['official_email']) ?></div></div>
 <?php if ($p['status'] === 'pending'): ?><div class="card shadow-sm mb-3"><div class="card-body"><form method="post"><?= csrf_field() ?>
  <label class="form-label">Internal note (admin only)</label><textarea name="internal_note" class="form-control mb-2" rows="2"></textarea>
  <label class="form-label">Rejection reason (mandatory to reject — shown to the club)</label><textarea name="reason" class="form-control mb-3" rows="3"></textarea>
  <button name="action" value="approve" class="btn btn-success">Approve & publish</button> <button name="action" value="reject" class="btn btn-danger">Reject</button></form></div></div>
 <?php elseif ($p['status'] === 'published'): ?><div class="card shadow-sm mb-3"><div class="card-body"><form method="post" data-confirm="Remove this post from the feed?"><?= csrf_field() ?><label class="form-label">Reason to remove</label><textarea name="reason" class="form-control mb-2" rows="2" required></textarea><button name="action" value="unpublish" class="btn btn-outline-danger">Unpublish</button></form></div></div><?php endif; ?>
 <div class="card shadow-sm"><div class="card-header">Approval history</div><ul class="list-group list-group-flush small"><?php foreach ($hist as $h): ?><li class="list-group-item"><strong><?= e($h['action']) ?></strong> · <?= dt($h['created_at']) ?><br><span class="text-muted"><?= e($h['email']) ?></span><?= $h['reason'] ? '<br>Reason: ' . e($h['reason']) : '' ?><?= $h['internal_note'] ? '<br><em>Note: ' . e($h['internal_note']) . '</em>' : '' ?></li><?php endforeach; ?></ul></div>
</div></div>
<?php require __DIR__ . '/../includes/footer.php';

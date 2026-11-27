<?php
// Renders one published post. Expects $p (row from feed_posts()). Viewer is a student.
$uid = user()['id'] ?? 0;
$comments = rows("SELECT c.*, s.full_name FROM post_comments c JOIN students s ON s.user_id=c.user_id WHERE c.post_id=? AND c.status='visible' ORDER BY c.created_at", [$p['id']]);
?>
<div class="card post-card mb-3 shadow-sm" id="post-<?= (int) $p['id'] ?>">
  <div class="card-body pb-2">
    <div class="d-flex align-items-center gap-2 mb-2">
      <?php if ($p['club_logo']): ?><img class="club-logo" src="<?= url($p['club_logo']) ?>" alt=""><?php else: ?><span class="club-logo"><?= e(mb_substr($p['club_name'], 0, 1)) ?></span><?php endif; ?>
      <div><a class="fw-semibold text-decoration-none" href="<?= url('student/clubs.php?id=' . (int) $p['club_id']) ?>"><?= e($p['club_name']) ?></a><?php if (($p['club_type'] ?? 'club') === 'department'): ?> <span class="badge bg-info">Department</span><?php endif; ?>
        <div class="small text-muted"><?= dt($p['published_at']) ?><?= $p['category'] ? ' · ' . e($p['category']) : '' ?></div></div>
    </div>
    <h5><?= e($p['title']) ?></h5>
    <p class="mb-2" style="white-space:pre-line"><?= e($p['description']) ?></p>
    <?php if ($p['event_date'] || $p['event_venue']): ?>
      <p class="small text-muted mb-2"><i class="bi bi-calendar-event"></i> <?= $p['event_date'] ? dt($p['event_date'], 'd M Y') : '' ?> <?= $p['event_time'] ? dt($p['event_time'], 'h:i A') : '' ?> <?= $p['event_venue'] ? '· ' . e($p['event_venue']) : '' ?></p>
    <?php endif; ?>
    <?php if ($p['extra_info']): ?><p class="small mb-2"><?= e($p['extra_info']) ?></p><?php endif; ?>
    <?php if ($p['attachment']): ?><a class="small" href="<?= url($p['attachment']) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> Attachment</a><?php endif; ?>
  </div>
  <?php if ($p['image']): ?><img class="post-img" src="<?= url($p['image']) ?>" alt="Post image"><?php endif; ?>
  <div class="card-body pt-2">
    <div class="d-flex gap-3 mb-2">
      <button class="btn btn-sm btn-link text-decoration-none like-btn <?= $p['liked'] ? 'liked' : '' ?>" data-id="<?= (int) $p['id'] ?>"><i class="bi bi-heart<?= $p['liked'] ? '-fill' : '' ?>"></i> <span class="count"><?= (int) $p['likes'] ?></span></button>
      <a class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="collapse" href="#cm-<?= (int) $p['id'] ?>"><i class="bi bi-chat"></i> <?= (int) $p['comments'] ?></a>
      <button class="btn btn-sm btn-link text-decoration-none save-btn ms-auto <?= $p['saved'] ? 'saved' : '' ?>" data-id="<?= (int) $p['id'] ?>"><i class="bi bi-bookmark<?= $p['saved'] ? '-fill' : '' ?>"></i> Save</button>
    </div>
    <div class="collapse" id="cm-<?= (int) $p['id'] ?>">
      <?php foreach ($comments as $c): ?>
        <div class="comment small">
          <strong><?= e($c['full_name']) ?></strong> <span class="text-muted"><?= dt($c['created_at']) ?><?= $c['updated_at'] ? ' (edited)' : '' ?></span>
          <div style="white-space:pre-line"><?= e($c['body']) ?></div>
          <?php if ((int) $c['user_id'] === $uid): ?>
            <a href="#" class="edit-comment me-2" data-id="<?= (int) $c['id'] ?>" data-body="<?= e($c['body']) ?>" onclick="return false">Edit</a>
            <form method="post" action="<?= url('api/comments.php') ?>" class="d-inline" data-confirm="Delete this comment?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>"><button class="btn btn-link btn-sm p-0 text-danger" data-no-loading="1">Delete</button></form>
          <?php else: ?>
            <a href="#" class="report-comment text-danger" data-id="<?= (int) $c['id'] ?>" onclick="return false">Report</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <form method="post" action="<?= url('api/comments.php') ?>" class="d-flex gap-2 mt-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="post_id" value="<?= (int) $p['id'] ?>">
        <input name="body" class="form-control form-control-sm" maxlength="1000" placeholder="Write a comment…" required>
        <button class="btn btn-sm btn-accent">Post</button>
      </form>
    </div>
  </div>
</div>

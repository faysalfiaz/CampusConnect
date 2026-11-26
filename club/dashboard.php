<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id'];
$cnt = fn($st) => (int) val('SELECT COUNT(*) FROM posts WHERE club_id=? AND status=?', [$cid, $st]);
$stats = [['Published', $cnt('published'), 'check-circle'], ['Pending', $cnt('pending'), 'hourglass-split'], ['Rejected', $cnt('rejected'), 'x-circle'], ['Drafts', $cnt('draft'), 'pencil'],
  ['Events', (int) val('SELECT COUNT(*) FROM events WHERE club_id=?', [$cid]), 'calendar-event'], ['Members', (int) val('SELECT COUNT(*) FROM club_members WHERE club_id=?', [$cid]), 'people'],
  ['Registrations', (int) val("SELECT COUNT(*) FROM event_registrations r JOIN events e ON e.id=r.event_id WHERE e.club_id=? AND r.status='registered'", [$cid]), 'journal-check'], ['Likes', (int) val('SELECT COUNT(*) FROM post_likes l JOIN posts p ON p.id=l.post_id WHERE p.club_id=?', [$cid]), 'heart']];
$rej = rows("SELECT id,title,rejection_reason FROM posts WHERE club_id=? AND status='rejected' ORDER BY updated_at DESC LIMIT 5", [$cid]);
$rejE = rows("SELECT id,title,rejection_reason FROM events WHERE club_id=? AND status='rejected' ORDER BY updated_at DESC LIMIT 5", [$cid]);
$activity = rows('SELECT action, details, created_at FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 8', [user()['id']]);
$noHead = true; $portal = 'club'; $title = $c['name'];
require __DIR__ . '/../includes/header.php'; ?><section class="dash-hero"><div class="dh-text"><span class="dh-chip"><i class="bi bi-stars"></i> <?= ($c['org_type'] ?? 'club') === 'department' ? 'Department' : 'Club' ?> portal</span><h2><?= e($c['name']) ?></h2><p>Manage your posts, events and attendance in one place.</p><div class="dh-actions"><a class="btn btn-light" href="<?= url('club/create-post.php') ?>"><i class="bi bi-megaphone"></i> New post</a><a class="btn btn-outline-light" href="<?= url('club/create-event.php') ?>"><i class="bi bi-calendar-plus"></i> New event</a><a class="btn btn-outline-light" href="<?= url('club/events.php') ?>"><i class="bi bi-qr-code-scan"></i> Scan attendance</a></div></div><div class="dh-date"><b><?= date('d') ?></b><span><?= date('M Y') ?></span><small><?= date('l') ?></small></div></section><?php  ?>
<div class="row g-3 mb-4"><?php foreach ($stats as [$l, $v, $i]): ?><div class="col-6 col-lg-3"><div class="card stat-card shadow-sm"><div class="card-body"><i class="bi bi-<?= $i ?> text-accent"></i> <span class="text-muted small"><?= $l ?></span><div class="num"><?= $v ?></div></div></div></div><?php endforeach; ?></div>
<div class="row g-3">
 <div class="col-lg-7"><div class="card shadow-sm"><div class="card-header">Needs your attention (admin feedback)</div><ul class="list-group list-group-flush">
  <?php foreach ($rej as $r): ?><li class="list-group-item"><span class="badge bg-danger">Post</span> <a href="<?= url('club/create-post.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a><div class="small text-muted"><?= e($r['rejection_reason']) ?></div></li><?php endforeach; ?>
  <?php foreach ($rejE as $r): ?><li class="list-group-item"><span class="badge bg-danger">Event</span> <a href="<?= url('club/create-event.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a><div class="small text-muted"><?= e($r['rejection_reason']) ?></div></li><?php endforeach; ?>
  <?php if (!$rej && !$rejE): ?><li class="list-group-item text-muted">No rejected items. 🎉</li><?php endif; ?></ul></div></div>
 <div class="col-lg-5"><div class="card shadow-sm"><div class="card-header">Club activity</div><ul class="list-group list-group-flush small">
  <?php foreach ($activity as $a): ?><li class="list-group-item"><?= e(str_replace('_', ' ', $a['action'])) ?> <span class="text-muted"><?= e($a['details']) ?> · <?= dt($a['created_at']) ?></span></li><?php endforeach; if (!$activity) echo '<li class="list-group-item text-muted">No activity yet.</li>'; ?></ul></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$club = current_club(); $portal = 'club'; $title = 'Event feedback'; $sel = (int) get('id');
$evs = rows("SELECT e.id,e.title,e.event_date,COUNT(f.id) n,AVG(f.rating) avg_r FROM events e LEFT JOIN event_feedback f ON f.event_id=e.id
  WHERE e.club_id=? AND e.status='approved' GROUP BY e.id,e.title,e.event_date ORDER BY e.event_date DESC", [$club['id']]);
$cm = $sel ? rows('SELECT f.rating,f.comment,f.created_at FROM event_feedback f JOIN events e ON e.id=f.event_id WHERE e.id=? AND e.club_id=? ORDER BY f.created_at DESC', [$sel, $club['id']]) : [];
require __DIR__ . '/../includes/header.php'; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-header">Ratings by event</div><div class="list-group list-group-flush">
<?php foreach ($evs as $x): ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="?id=<?= (int) $x['id'] ?>">
  <span><?= e($x['title']) ?><br><small class="text-muted"><?= e(dt($x['event_date'], 'd M Y')) ?></small></span>
  <span class="text-end"><?= $x['n'] ? '<strong>' . number_format((float) $x['avg_r'], 1) . '</strong> ★<br><small class="text-muted">' . (int) $x['n'] . ' reviews</small>' : '<small class="text-muted">No reviews yet</small>' ?></span></a>
<?php endforeach; if (!$evs): ?><div class="empty"><i class="bi bi-star"></i>No approved events yet.</div><?php endif; ?></div></div></div>
<div class="col-lg-6"><?php if ($sel): ?><div class="card"><div class="card-header">Comments</div><div class="list-group list-group-flush">
<?php foreach ($cm as $c): ?><div class="list-group-item"><span class="text-warning"><?= str_repeat('★', (int) $c['rating']) ?></span><span class="text-muted"><?= str_repeat('★', 5 - (int) $c['rating']) ?></span> <small class="text-muted"><?= e(dt($c['created_at'], 'd M Y')) ?></small><div><?= e($c['comment'] ?: 'No comment.') ?></div></div>
<?php endforeach; if (!$cm): ?><div class="empty">No feedback for this event yet.</div><?php endif; ?></div></div><?php endif; ?></div></div>
<?php require __DIR__ . '/../includes/footer.php';

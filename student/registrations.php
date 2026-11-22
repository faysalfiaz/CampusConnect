<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id'];
$regs = rows('SELECT r.*, e.title, e.event_date, e.start_time, e.venue, e.status estatus, e.end_time, e.registration_deadline, c.name club_name FROM event_registrations r JOIN events e ON e.id=r.event_id JOIN clubs c ON c.id=e.club_id WHERE r.user_id=? ORDER BY r.created_at DESC', [$uid]);
$portal = 'student'; $title = 'My Registrations';
require __DIR__ . '/../includes/header.php'; ?>
<?php if (!$regs): ?><div class="empty"><i class="bi bi-journal"></i>No registrations yet. <a href="<?= url('student/events.php') ?>">Browse events</a></div><?php else: ?>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($regs as $r): ?>
<tr><td><a href="<?= url('student/events.php?id=' . $r['event_id']) ?>"><?= e($r['title']) ?></a><div class="small text-muted"><?= e($r['club_name']) ?></div></td>
<td><?= dt($r['event_date'] . ' ' . $r['start_time']) ?></td><td><?= e($r['venue']) ?></td>
<td><?= $r['estatus'] === 'cancelled' ? '<span class="badge bg-danger">Event cancelled</span>' : ($r['status'] === 'registered' ? '<span class="badge bg-success">Registered</span>' : ($r['status'] === 'waitlist' ? '<span class="badge bg-warning text-dark">Waiting list</span>' : '<span class="badge bg-secondary">Cancelled</span>')) ?><div class="small text-muted"><?= dt($r['cancelled_at'] ?: $r['created_at']) ?></div></td>
<td><?php if ($r['status'] !== 'cancelled' && $r['estatus'] === 'approved' && strtotime($r['event_date'] . ' ' . $r['end_time']) > time()): ?>
<form method="post" action="<?= url('api/registrations.php') ?>" data-confirm="Cancel this registration?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $r['event_id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-sm btn-outline-danger">Cancel</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php';

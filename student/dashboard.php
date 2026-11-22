<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $s = current_student();
$stats = [
 ['Registered events', val("SELECT COUNT(*) FROM event_registrations WHERE user_id=? AND status='registered'", [$uid]), 'journal-check'],
 ['Tickets', val("SELECT COUNT(*) FROM event_tickets t JOIN event_registrations r ON r.id=t.registration_id WHERE r.user_id=?", [$uid]), 'qr-code'],
 ['Attended', val('SELECT COUNT(*) FROM attendance a JOIN event_tickets t ON t.id=a.ticket_id JOIN event_registrations r ON r.id=t.registration_id WHERE r.user_id=?', [$uid]), 'check2-circle'],
 ['Certificates', val('SELECT COUNT(*) FROM certificates WHERE user_id=?', [$uid]), 'award'],
];
$upcoming = rows("SELECT e.*, c.name club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.status='approved' AND CONCAT(e.event_date,' ',e.end_time) >= NOW() ORDER BY e.event_date, e.start_time LIMIT 5");
$notes = rows('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5', [$uid]);
$emergency = rows('SELECT * FROM notices WHERE is_emergency=1 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC');
$noHead = true; $portal = 'student'; $title = 'Welcome, ' . $s['full_name'];
require __DIR__ . '/../includes/header.php'; ?><section class="dash-hero"><div class="dh-text"><span class="dh-chip"><i class="bi bi-stars"></i> Student portal</span><h2>Hi, <?= e(explode(' ', trim($s['full_name']))[0]) ?></h2><p>Here is what is happening on campus today.</p><div class="dh-actions"><a class="btn btn-light" href="<?= url('student/events.php') ?>"><i class="bi bi-calendar-event"></i> Browse events</a><a class="btn btn-outline-light" href="<?= url('student/calendar.php') ?>"><i class="bi bi-calendar3"></i> Calendar</a><a class="btn btn-outline-light" href="<?= url('student/tickets.php') ?>"><i class="bi bi-ticket-perforated"></i> My tickets</a></div></div><div class="dh-date"><b><?= date('d') ?></b><span><?= date('M Y') ?></span><small><?= date('l') ?></small></div></section><?php 
foreach ($emergency as $n): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?= e($n['title']) ?></strong> — <?= e($n['body']) ?></div><?php endforeach; ?>
<?php $soonEv = rows("SELECT e.id,e.title,e.event_date,e.start_time,e.venue FROM events e JOIN event_registrations r ON r.event_id=e.id AND r.user_id=? AND r.status='registered' WHERE e.status='approved' AND TIMESTAMP(e.event_date,e.start_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR) ORDER BY e.event_date,e.start_time", [$uid]);
foreach ($soonEv as $sv): ?><div class="alert alert-info d-flex align-items-center gap-2"><i class="bi bi-alarm"></i><div><strong>Starting soon:</strong> <a href="<?= url('student/events.php?id=' . (int) $sv['id']) ?>"><?= e($sv['title']) ?></a> · <?= e(dt($sv['event_date'] . ' ' . $sv['start_time'], 'D, h:i A')) ?> · <?= e($sv['venue']) ?></div></div><?php endforeach; ?>
<div class="row g-3 mb-4">
<?php foreach ($stats as [$l, $v, $i]): ?><div class="col-6 col-lg-3"><div class="card stat-card shadow-sm"><div class="card-body"><i class="bi bi-<?= $i ?> text-accent"></i> <span class="text-muted small"><?= $l ?></span><div class="num"><?= (int) $v ?></div></div></div></div><?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="card shadow-sm"><div class="card-header">Upcoming events</div><div class="list-group list-group-flush">
    <?php foreach ($upcoming as $e): ?><a class="list-group-item list-group-item-action" href="<?= url('student/events.php?id=' . $e['id']) ?>"><div class="fw-semibold"><?= e($e['title']) ?></div><div class="small text-muted"><?= e($e['club_name']) ?> · <?= dt($e['event_date'] . ' ' . $e['start_time']) ?> · <?= e($e['venue']) ?></div></a><?php endforeach; ?>
    <?php if (!$upcoming): ?><div class="empty"><i class="bi bi-calendar-x"></i>No upcoming events.</div><?php endif; ?>
  </div></div></div>
  <div class="col-lg-5"><div class="card shadow-sm"><div class="card-header">Recent notifications</div><ul class="list-group list-group-flush">
    <?php foreach ($notes as $n): ?><li class="list-group-item small"><strong><?= e($n['title']) ?></strong><br><?= e($n['body']) ?></li><?php endforeach; ?>
    <?php if (!$notes): ?><li class="list-group-item text-muted">Nothing yet.</li><?php endif; ?>
  </ul></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';

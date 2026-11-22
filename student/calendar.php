<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$portal = 'student'; $title = 'Event calendar'; $uid = user()['id'];
$m = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', get('m')) ? get('m') : date('Y-m');
$first = strtotime($m . '-01'); $days = (int) date('t', $first); $lead = (int) date('N', $first) - 1;
$evs = rows("SELECT e.id,e.title,e.event_date,e.start_time,e.venue,c.name club_name,
  (SELECT r.status FROM event_registrations r WHERE r.event_id=e.id AND r.user_id=?) reg
  FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.status='approved' AND e.event_date BETWEEN ? AND ?
  ORDER BY e.event_date,e.start_time", [$uid, date('Y-m-01', $first), date('Y-m-t', $first)]);
$by = []; foreach ($evs as $x) $by[(int) date('j', strtotime($x['event_date']))][] = $x;
$prev = date('Y-m', strtotime('-1 month', $first)); $next = date('Y-m', strtotime('+1 month', $first));
$today = date('Y-m') === $m ? (int) date('j') : 0;
require __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <a class="btn btn-sm btn-outline-secondary" href="?m=<?= e($prev) ?>"><i class="bi bi-chevron-left"></i></a>
  <h5 class="m-0"><?= e(date('F Y', $first)) ?></h5>
  <a class="btn btn-sm btn-outline-secondary" href="?m=<?= e($next) ?>"><i class="bi bi-chevron-right"></i></a>
</div>
<div class="cal d-none d-md-grid">
  <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $w): ?><div class="cal-h"><?= $w ?></div><?php endforeach; ?>
  <?php for ($i = 0; $i < $lead; $i++): ?><div class="cal-c cal-off"></div><?php endfor; ?>
  <?php for ($d = 1; $d <= $days; $d++): ?>
    <div class="cal-c <?= $d === $today ? 'cal-today' : '' ?>"><span class="cal-d"><?= $d ?></span>
      <?php foreach (($by[$d] ?? []) as $x): ?>
        <a class="cal-ev <?= $x['reg'] === 'registered' ? 'cal-reg' : '' ?>" href="<?= url('student/events.php?id=' . (int) $x['id']) ?>" title="<?= e($x['club_name'] . ' · ' . $x['venue']) ?>"><?= e(date('g:i A', strtotime($x['start_time']))) ?> <?= e($x['title']) ?></a>
      <?php endforeach; ?></div>
  <?php endfor; ?>
</div>
<div class="d-md-none">
  <?php foreach ($by as $d => $list): foreach ($list as $x): ?>
    <a class="card mb-2 text-decoration-none" href="<?= url('student/events.php?id=' . (int) $x['id']) ?>"><div class="card-body py-2">
      <div class="fw-semibold"><?= e($x['title']) ?></div><div class="small text-muted"><?= e(dt($x['event_date'] . ' ' . $x['start_time'], 'D, d M · h:i A')) ?> · <?= e($x['venue']) ?></div></div></a>
  <?php endforeach; endforeach; ?>
</div>
<?php if (!$evs): ?><div class="empty"><i class="bi bi-calendar-x"></i>No approved events this month.</div><?php endif; ?>
<p class="small text-muted mt-3"><span class="cal-key"></span> Events you registered for are highlighted.</p>
<?php require __DIR__ . '/../includes/footer.php';

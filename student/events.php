<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $portal = 'student'; $id = (int) get('id'); $stu = current_student();
if ($id) {
    $e = row("SELECT e.*, c.name club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.id=? AND e.status IN ('approved','cancelled')", [$id]);
    if (!$e) { http_response_code(404); exit('Event not found.'); }
    $reg = row('SELECT * FROM event_registrations WHERE event_id=? AND user_id=?', [$id, $uid]);
    $phase = event_phase($e);
    $canReg = $phase === 'Registration Open' && (!$reg || $reg['status'] === 'cancelled');
    $count = (int) val("SELECT COUNT(*) FROM event_registrations WHERE event_id=? AND status IN ('registered','waitlist')", [$id]);
    $title = $e['title'];
    require __DIR__ . '/../includes/header.php'; ?>
    <?php if ($e['status'] === 'cancelled'): ?><div class="alert alert-danger d-flex gap-2 align-items-start"><i class="bi bi-x-octagon-fill fs-4"></i><div><strong>This event has been cancelled.</strong><?= $e['rejection_reason'] ? '<br>Reason: ' . e($e['rejection_reason']) : '' ?></div></div><?php endif; ?>
    <div class="row g-4"><div class="col-lg-7">
      <div class="ev-cover" style="<?= $e['poster'] ? "background-image:url('" . e(url($e['poster'])) . "')" : '' ?>"><span class="ev-date"><b><?= e(dt($e['event_date'], 'd')) ?></b><?= e(dt($e['event_date'], 'M')) ?></span></div>
      <p><?= status_badge($phase) ?> <span class="text-muted">by <?= e($e['club_name']) ?></span></p>
      <p style="white-space:pre-line"><?= e($e['description']) ?></p>
      <?php if ($e['rules']): ?><h6>Rules</h6><p style="white-space:pre-line"><?= e($e['rules']) ?></p><?php endif; ?>
      <?php if ($e['requirements']): ?><h6>Registration requirements</h6><p style="white-space:pre-line"><?= e($e['requirements']) ?></p><?php endif; ?>
    </div><div class="col-lg-5"><div class="card shadow-sm"><div class="card-body">
      <p class="mb-1"><i class="bi bi-calendar-event"></i> <?= dt($e['event_date'], 'D, d M Y') ?></p>
      <p class="mb-1"><i class="bi bi-clock"></i> <?= dt($e['start_time'], 'h:i A') ?> – <?= dt($e['end_time'], 'h:i A') ?></p>
      <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= e($e['venue']) ?></p>
      <p class="mb-1"><i class="bi bi-hourglass-split"></i> Register by <?= dt($e['registration_deadline']) ?></p>
      <p class="mb-3"><i class="bi bi-people"></i> <?= $e['max_participants'] ? 'Limited seats' : 'Open to everyone' ?><?= $e['eligible_dept'] ? ' · ' . e($e['eligible_dept']) . ' only' : '' ?></p>
      <?php if ($reg && $reg['status'] !== 'cancelled'): ?>
        <div class="alert alert-success py-2">You are <?= $reg['status'] === 'waitlist' ? 'on the <strong>waiting list</strong>' : '<strong>registered</strong>' ?>.</div>
        <form method="post" action="<?= url('api/registrations.php') ?>" data-confirm="Cancel your registration?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $id ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-outline-danger">Cancel registration</button></form>
      <?php elseif ($canReg): ?>
        <form method="post" action="<?= url('api/registrations.php') ?>"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $id ?>"><input type="hidden" name="action" value="register"><button class="btn btn-accent"><?= ($e['max_participants'] && $count >= $e['max_participants']) ? 'Join waiting list' : 'Register now' ?></button></form>
      <?php else: ?><div class="text-muted">Registration is not open.</div><?php endif; ?>
    </div></div></div></div>
    <?php require __DIR__ . '/../includes/footer.php'; exit;
}
$qs = get('q'); $cat = get('category'); $when = get('when', 'upcoming');
$w = "e.status='approved'"; $p = [];
if ($when === 'upcoming') $w .= " AND CONCAT(e.event_date,' ',e.end_time) >= NOW()"; elseif ($when === 'past') $w .= " AND CONCAT(e.event_date,' ',e.end_time) < NOW()";
if ($qs !== '') { $w .= ' AND (e.title LIKE ? OR e.venue LIKE ?)'; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($cat !== '') { $w .= ' AND e.category=?'; $p[] = $cat; }
$events = rows("SELECT e.*, c.name club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE $w ORDER BY e.event_date, e.start_time LIMIT 100", $p);
$cats = rows("SELECT DISTINCT category FROM events WHERE status='approved' AND category IS NOT NULL AND category<>''");
$title = 'Events';
require __DIR__ . '/../includes/header.php'; ?>
<form class="row g-2 mb-3"><div class="col-md-4"><input name="q" class="form-control" placeholder="Search events…" value="<?= e($qs) ?>"></div>
 <div class="col-6 col-md-3"><select name="category" class="form-select"><option value="">All categories</option><?php foreach ($cats as $c): ?><option <?= $cat === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option><?php endforeach; ?></select></div>
 <div class="col-6 col-md-3"><select name="when" class="form-select"><option value="upcoming" <?= $when === 'upcoming' ? 'selected' : '' ?>>Upcoming</option><option value="past" <?= $when === 'past' ? 'selected' : '' ?>>Past</option><option value="all" <?= $when === 'all' ? 'selected' : '' ?>>All</option></select></div>
 <div class="col-md-2"><button class="btn btn-accent w-100">Search</button></div></form>
<div class="row g-3">
<?php foreach ($events as $e): ?>
 <div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm">
   <?php if ($e['poster']): ?><img src="<?= url($e['poster']) ?>" class="card-img-top" style="height:160px;object-fit:cover" alt=""><?php endif; ?>
   <div class="card-body"><?= status_badge(event_phase($e)) ?><h6 class="mt-2"><?= e($e['title']) ?></h6>
   <div class="small text-muted"><?= e($e['club_name']) ?><br><?= dt($e['event_date'], 'd M Y') ?> · <?= e($e['venue']) ?></div></div>
   <div class="card-footer bg-transparent"><a class="btn btn-sm btn-accent" href="<?= url('student/events.php?id=' . $e['id']) ?>">Details</a></div></div></div>
<?php endforeach; if (!$events): ?><div class="empty"><i class="bi bi-calendar-x"></i>No events found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php';
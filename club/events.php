<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $uid = user()['id'];
if (is_post()) {
    csrf_check(); $eid = (int) post('id'); $e = row('SELECT * FROM events WHERE id=? AND club_id=?', [$eid, $cid]);
    if ($e && post('action') === 'cancel' && in_array($e['status'], ['approved', 'pending'], true)) {
        $reason = post('reason'); q("UPDATE events SET status='cancelled', rejection_reason=? WHERE id=?", [$reason ?: null, $eid]); $notified = 0;
        q("INSERT INTO event_approvals (event_id, action, actor_user_id, reason) VALUES (?, 'cancelled', ?, ?)", [$eid, $uid, post('reason') ?: null]);
        foreach (rows("SELECT user_id FROM event_registrations WHERE event_id=? AND status IN ('registered','waitlist')", [$eid]) as $r) { notify((int) $r['user_id'], 'Event cancelled: ' . $e['title'], mb_substr($e['title'] . ' on ' . date('d M Y', strtotime($e['event_date'])) . ' has been cancelled by ' . $c['name'] . ($reason ? '. Reason: ' . $reason : '.'), 0, 490), 'student/events.php?id=' . $eid); $notified++; }
        log_activity('event_cancelled', $e['title']); flash('success', 'Event cancelled. ' . $notified . ' student(s) were notified.');
    } elseif ($e && post('action') === 'delete' && in_array($e['status'], ['draft', 'rejected'], true)) { q('DELETE FROM events WHERE id=?', [$eid]); flash('success', 'Event deleted.'); }
    redirect('club/events.php');
}
$events = rows("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id=e.id AND r.status='registered') regs FROM events e WHERE e.club_id=? ORDER BY e.event_date DESC", [$cid]);
$portal = 'club'; $title = 'Events';
require __DIR__ . '/../includes/header.php'; ?>
<?php if (!$events): ?><div class="empty"><i class="bi bi-calendar-plus"></i>No events yet. <a href="<?= url('club/create-event.php') ?>">Create one</a></div><?php endif; ?>
<?php foreach ($events as $e): $ph = event_phase($e); ?>
<div class="card mb-2 shadow-sm"><div class="card-body d-flex justify-content-between flex-wrap gap-2">
 <div><strong><?= e($e['title']) ?></strong> <?= status_badge($e['status'] === 'approved' ? $ph : $e['status']) ?>
  <div class="small text-muted"><?= dt($e['event_date'], 'd M Y') ?> · <?= e($e['venue']) ?> · <?= (int) $e['regs'] ?><?= $e['max_participants'] ? '/' . (int) $e['max_participants'] : '' ?> registered</div>
  <?php if ($e['status'] === 'rejected'): ?><div class="alert alert-danger py-1 px-2 mt-2 mb-0 small"><strong>Admin feedback:</strong> <?= e($e['rejection_reason']) ?></div><?php endif; ?></div>
 <div class="d-flex gap-1 align-items-start flex-wrap">
  <?php if (in_array($e['status'], ['draft', 'rejected', 'pending', 'approved'], true)): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('club/create-event.php?id=' . $e['id']) ?>"><?= $e['status'] === 'rejected' ? 'Edit & resubmit' : 'Edit' ?></a><?php endif; ?>
  <?php if ($e['status'] === 'approved'): ?><a class="btn btn-sm btn-accent" href="<?= url('club/event-manage.php?id=' . $e['id']) ?>">Manage / Attendance</a><?php endif; ?>
  <?php if (in_array($e['status'], ['approved', 'pending'], true)): ?><form method="post" class="d-flex gap-1 flex-wrap" data-confirm="Cancel this event? Every registered student will get a notification."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><input type="hidden" name="action" value="cancel"><input name="reason" class="form-control form-control-sm" style="width:200px" placeholder="Reason (students will see it)" required><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-octagon"></i> Cancel event</button></form><?php endif; ?>
  <?php if (in_array($e['status'], ['draft', 'rejected'], true)): ?><form method="post" data-confirm="Delete this event?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger">Delete</button></form><?php endif; ?>
 </div></div></div>
<?php endforeach; require __DIR__ . '/../includes/footer.php';
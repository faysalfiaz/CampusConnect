<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$aid = user()['id'];
if (is_post()) {
    csrf_check(); $id = (int) post('id'); $a = post('action');
    $e = row('SELECT e.*, c.user_id club_user, c.name club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.id=?', [$id]);
    if ($e && $e['status'] === 'pending') {
        if ($a === 'approve') {
            q("UPDATE events SET status='approved', approved_at=NOW(), rejection_reason=NULL WHERE id=?", [$id]);
            q("INSERT INTO event_approvals (event_id, action, actor_user_id) VALUES (?, 'approved', ?)", [$id, $aid]);
            notify((int) $e['club_user'], 'Event approved', '"' . $e['title'] . '" is now public and open for registration.', 'club/events.php');
            notify_all_students('New event: ' . $e['title'], $e['club_name'] . ' · ' . dt($e['event_date'], 'd M Y'), 'student/events.php?id=' . $id);
            log_activity('event_approved', $e['title']); flash('success', 'Event approved.');
        } elseif ($a === 'reject') {
            $reason = post('reason');
            if (mb_strlen($reason) < 10) flash('error', 'A clear rejection reason (10+ characters) is mandatory.');
            else {
                q("UPDATE events SET status='rejected', rejection_reason=? WHERE id=?", [$reason, $id]);
                q("INSERT INTO event_approvals (event_id, action, actor_user_id, reason) VALUES (?, 'rejected', ?, ?)", [$id, $aid, $reason]);
                notify((int) $e['club_user'], 'Event rejected', '"' . $e['title'] . '": ' . mb_strimwidth($reason, 0, 300, '…'), 'club/create-event.php?id=' . $id);
                log_activity('event_rejected', $e['title']); flash('success', 'Event rejected.');
            }
        }
    } elseif ($e && $a === 'cancel' && $e['status'] === 'approved') {
        q("UPDATE events SET status='cancelled' WHERE id=?", [$id]);
        q("INSERT INTO event_approvals (event_id, action, actor_user_id, reason) VALUES (?, 'cancelled', ?, ?)", [$id, $aid, post('reason') ?: 'Cancelled by admin']);
        foreach (rows("SELECT user_id FROM event_registrations WHERE event_id=? AND status IN ('registered','waitlist')", [$id]) as $r) notify((int) $r['user_id'], 'Event cancelled', $e['title'] . ' has been cancelled.', 'student/registrations.php');
        notify((int) $e['club_user'], 'Event cancelled by admin', $e['title'], 'club/events.php'); log_activity('event_cancelled', $e['title']); flash('success', 'Event cancelled.');
    }
    redirect('admin/events.php?status=' . urlencode(get('status', 'pending')));
}
$st = get('status', 'pending'); if (!in_array($st, ['pending', 'approved', 'rejected', 'cancelled'], true)) $st = 'pending';
$events = rows('SELECT e.*, c.name club_name, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id=e.id AND r.status="registered") regs FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.status=? ORDER BY e.event_date', [$st]);
$portal = 'admin'; $title = 'Event Approvals';
require __DIR__ . '/../includes/header.php'; ?>
<ul class="nav nav-pills mb-3"><?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $k): ?><li class="nav-item"><a class="nav-link <?= $st === $k ? 'active' : '' ?>" href="?status=<?= $k ?>"><?= ucfirst($k) ?></a></li><?php endforeach; ?></ul>
<?php if (!$events): ?><div class="empty"><i class="bi bi-calendar-x"></i>Nothing here.</div><?php endif; ?>
<?php foreach ($events as $e): ?><div class="card mb-3 shadow-sm"><div class="card-body"><div class="d-flex justify-content-between flex-wrap"><div><h6><?= e($e['title']) ?> <?= status_badge($e['status']) ?></h6><div class="small text-muted"><?= e($e['club_name']) ?> · <?= dt($e['event_date'], 'd M Y') ?> <?= dt($e['start_time'], 'h:i A') ?>–<?= dt($e['end_time'], 'h:i A') ?> · <?= e($e['venue']) ?> · cap <?= $e['max_participants'] ?: '∞' ?> · <?= (int) $e['regs'] ?> registered</div></div></div>
<p class="mt-2 mb-2 small" style="white-space:pre-line"><?= e($e['description']) ?></p>
<?php if ($e['status'] === 'pending'): ?><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><div class="col-md-7"><input name="reason" class="form-control form-control-sm" placeholder="Rejection reason (mandatory to reject)"></div><div class="col-md-5"><button name="action" value="approve" class="btn btn-sm btn-success">Approve</button> <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button></div></form>
<?php elseif ($e['status'] === 'approved'): ?><form method="post" data-confirm="Cancel this event and notify registrants?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-sm btn-outline-danger">Cancel event</button></form>
<?php elseif ($e['status'] === 'rejected'): ?><div class="small text-danger">Reason: <?= e($e['rejection_reason']) ?></div><?php endif; ?></div></div><?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php';

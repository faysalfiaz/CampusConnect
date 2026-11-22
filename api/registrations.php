<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_post() || !user() || user()['role'] !== 'student') { http_response_code(403); exit('Forbidden'); }
csrf_check();
$uid = user()['id']; $eid = (int) post('event_id'); $a = post('action');
$ev = row("SELECT * FROM events WHERE id=? AND status='approved'", [$eid]);
if (!$ev) { flash('error', 'Event not available.'); redirect_back('student/events.php'); }
$stu = current_student();
if ($a === 'register') {
    $end = strtotime($ev['event_date'] . ' ' . $ev['end_time']);
    $existing = row('SELECT * FROM event_registrations WHERE event_id=? AND user_id=?', [$eid, $uid]);
    if (time() > strtotime($ev['registration_deadline']) || time() > $end) flash('error', 'Registration is closed for this event.');
    elseif ($ev['eligible_dept'] && strcasecmp($ev['eligible_dept'], $stu['department']) !== 0) flash('error', 'This event is only for ' . $ev['eligible_dept'] . ' students.');
    elseif ($existing && $existing['status'] !== 'cancelled') flash('error', 'You are already registered.');
    else {
        // Lock the event row so two simultaneous registrations cannot both take the last seat.
        db()->beginTransaction();
        try {
        q('SELECT id FROM events WHERE id=? FOR UPDATE', [$eid]);
        $full = $ev['max_participants'] && reg_count($eid) >= (int) $ev['max_participants'];
        $status = $full ? 'waitlist' : 'registered';
        if ($existing) q('UPDATE event_registrations SET status=?, cancelled_at=NULL, created_at=NOW() WHERE id=?', [$status, $existing['id']]);
        else { q('INSERT INTO event_registrations (event_id,user_id,status) VALUES (?,?,?)', [$eid, $uid, $status]); }
        $rid = $existing ? (int) $existing['id'] : insert_id();
        if (!$full) issue_ticket($rid);
        db()->commit();
        } catch (Throwable $ex) { if (db()->inTransaction()) db()->rollBack(); throw $ex; }
        if (!$full) { notify($uid, 'Registration confirmed', 'You are registered for ' . $ev['title'] . '. Your QR ticket is ready.', 'student/tickets.php'); flash('success', 'Registered! Your ticket is in "Tickets".'); }
        else { notify($uid, 'Added to waiting list', $ev['title'] . ' is full. You will be notified if a seat opens.', 'student/registrations.php'); flash('info', 'Event is full — you were added to the waiting list.'); }
        log_activity('event_register', $ev['title']);
    }
} elseif ($a === 'cancel') {
    $r = row("SELECT * FROM event_registrations WHERE event_id=? AND user_id=? AND status IN ('registered','waitlist')", [$eid, $uid]);
    if ($r) {
        $att = val('SELECT 1 FROM attendance a JOIN event_tickets t ON t.id=a.ticket_id WHERE t.registration_id=?', [$r['id']]);
        if ($att) flash('error', 'Cannot cancel after attendance was recorded.');
        else {
            q("UPDATE event_registrations SET status='cancelled', cancelled_at=NOW() WHERE id=?", [$r['id']]);
            q('DELETE FROM event_tickets WHERE registration_id=?', [$r['id']]);
            promote_waitlist($eid); flash('success', 'Registration cancelled.'); log_activity('event_cancel_registration', $ev['title']);
        }
    }
}
redirect_back('student/registrations.php');

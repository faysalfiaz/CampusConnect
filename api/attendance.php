<?php
// Called by the QR scanner in club/event-manage.php. Returns JSON.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
function out($ok, $msg, $extra = []) { echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra)); exit; }
if (!is_post() || !user() || user()['role'] !== 'club') { http_response_code(403); out(false, 'Forbidden'); }
csrf_check();
$club = current_club(); $eid = (int) post('event_id'); $code = strtoupper(post('code'));
if (preg_match('/code=([A-Z0-9-]+)/i', $code, $m)) $code = strtoupper($m[1]);   // accept full verify URLs too
$ev = row('SELECT * FROM events WHERE id=? AND club_id=?', [$eid, $club['id']]);
if (!$ev || $ev['status'] !== 'approved') out(false, 'Event not found or not approved.');
$t = row("SELECT t.id, r.status, r.event_id, s.full_name, s.student_code FROM event_tickets t JOIN event_registrations r ON r.id=t.registration_id JOIN students s ON s.user_id=r.user_id WHERE t.ticket_code=?", [$code]);
if (!$t) out(false, 'Invalid ticket.');
if ((int) $t['event_id'] !== $eid) out(false, 'This ticket is for a different event.');
if ($t['status'] !== 'registered') out(false, 'Registration is not active.');
if (val('SELECT id FROM attendance WHERE ticket_id=?', [$t['id']])) out(false, 'Already scanned — duplicate entry blocked.', ['name' => $t['full_name']]);
q('INSERT INTO attendance (ticket_id, event_id, scanned_by_user_id) VALUES (?,?,?)', [$t['id'], $eid, user()['id']]);
log_activity('attendance_scanned', $ev['title'] . ' / ' . $t['student_code']);
out(true, 'Attendance recorded.', ['name' => $t['full_name'], 'student_code' => $t['student_code']]);

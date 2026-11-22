<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_post() || !user() || user()['role'] !== 'student') { http_response_code(403); exit('Forbidden'); }
csrf_check();
$uid = user()['id']; $eid = (int) post('event_id'); $rating = (int) post('rating'); $comment = mb_substr(post('comment'), 0, 500);
$attended = val('SELECT 1 FROM attendance a JOIN event_tickets t ON t.id=a.ticket_id JOIN event_registrations r ON r.id=t.registration_id WHERE a.event_id=? AND r.user_id=?', [$eid, $uid]);
if (!$attended) { flash('error', 'You can only review events you attended.'); redirect('student/feedback.php'); }
if ($rating < 1 || $rating > 5) { flash('error', 'Choose a rating from 1 to 5 stars.'); redirect('student/feedback.php'); }
q('INSERT INTO event_feedback (event_id,user_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)', [$eid, $uid, $rating, $comment ?: null]);
log_activity('event_feedback', 'event ' . $eid, $uid);
flash('success', 'Feedback saved. Thank you.');
redirect('student/feedback.php');

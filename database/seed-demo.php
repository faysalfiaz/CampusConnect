<?php
// DEMO DATA for screenshots and presentations. Run once on localhost, then DELETE this file.
// Open http://localhost/CampusConnect-Pro/database/seed-demo.php   (needs the v2 tables)
require_once __DIR__ . '/../includes/functions.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Localhost only.'); }
if (val("SELECT COUNT(*) FROM users WHERE email='demo.club@campus.test'")) exit('Demo data already exists.');
$pw = password_hash('Demo@1234', PASSWORD_DEFAULT);
$mkuser = function (string $role, string $email) use ($pw): int { q("INSERT INTO users (role,email,password_hash,email_verified_at) VALUES (?,?,?,NOW())", [$role, $email, $pw]); return insert_id(); };

$cu = $mkuser('club', 'demo.club@campus.test');
q("INSERT INTO clubs (user_id,name,category,description,official_email) VALUES (?,?,?,?,?)", [$cu, 'Tech & Robotics Club', 'Technology', 'Workshops, hackathons and robot builds.', 'demo.club@campus.test']);
$cid = insert_id();
$stu = [];
foreach ([['Ayesha Rahman', 'CSE'], ['Tanvir Hasan', 'EEE'], ['Nusrat Jahan', 'BBA'], ['Rafi Ahmed', 'CSE']] as $i => [$name, $dept]) {
    $uid = $mkuser('student', 'student' . ($i + 1) . '@campus.test');
    q("INSERT INTO students (user_id,full_name,student_code,department,batch,semester) VALUES (?,?,?,?,?,?)", [$uid, $name, 'DEMO-00' . ($i + 1), $dept, '2023', '5th']);
    $stu[] = $uid;
}
$mkevent = function (string $title, int $offsetDays, string $desc, string $venue) use ($cid): int {
    $d = date('Y-m-d', strtotime("$offsetDays days"));
    q("INSERT INTO events (club_id,title,description,category,event_date,start_time,end_time,venue,max_participants,registration_deadline,status,approved_at) VALUES (?,?,?,?,?,?,?,?,?,?,'approved',NOW())",
      [$cid, $title, $desc, 'Workshop', $d, '15:00:00', '17:30:00', $venue, 60, date('Y-m-d 23:59:00', strtotime(($offsetDays - 1) . ' days'))]);
    return insert_id();
};
$past = [$mkevent('Intro to Arduino', -20, 'Hands-on microcontroller basics.', 'Lab 2'), $mkevent('Hack the Campus', -10, 'A 6-hour mini hackathon.', 'Main Auditorium'), $mkevent('Robot Soccer Demo', -3, 'Watch and build line-following bots.', 'Seminar Hall')];
$future = [$mkevent('AI Study Circle', 2, 'Weekly paper reading session.', 'Room 301'), $mkevent('Resume Clinic', 9, 'Get your CV reviewed by seniors.', 'Seminar Hall'), $mkevent('Open Source Night', 16, 'Make your first pull request.', 'Lab 1')];
$comments = ['Well organised and practical.', 'Loved the hands-on part.', 'Could have started on time.', 'Great speakers, more seats next time.'];
foreach (array_merge($past, $future) as $ei) foreach ($stu as $k => $uid) {
    if (in_array($ei, $future, true) && $k > 1) continue;
    q("INSERT INTO event_registrations (event_id,user_id,status) VALUES (?,?, 'registered')", [$ei, $uid]); $rid = insert_id(); issue_ticket($rid);
    if (in_array($ei, $past, true) && $k < 3) {
        $tid = (int) val('SELECT id FROM event_tickets WHERE registration_id=?', [$rid]);
        q('INSERT INTO attendance (ticket_id,event_id,scanned_by_user_id) VALUES (?,?,?)', [$tid, $ei, $cu]);
        q('INSERT INTO event_feedback (event_id,user_id,rating,comment) VALUES (?,?,?,?)', [$ei, $uid, 3 + (($k + $ei) % 3), $comments[($k + $ei) % 4]]);
    }
}
try {
    foreach ([['Hack the Campus winners', 'Congratulations to the three teams who shipped working demos in six hours.'], ['Robotics lab is open', 'Come by Lab 2 on weekdays to borrow kits.']] as [$t, $b])
        q("INSERT INTO posts (club_id,title,description,category,status,published_at) VALUES (?,?,?,?,'published',NOW())", [$cid, $t, $b, 'Announcement']);
} catch (Throwable $e) { echo 'Posts skipped: ' . e($e->getMessage()) . '<br>'; }
exit('Demo data created. Logins: demo.club@campus.test and student1..4@campus.test, password Demo@1234. Now delete database/seed-demo.php.');

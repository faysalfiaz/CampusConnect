<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!is_post() || !user() || user()['role'] !== 'student') { http_response_code(403); echo '{"ok":false}'; exit; }
csrf_check();
$pid = (int) post('post_id'); $uid = user()['id'];
if (!val("SELECT p.id FROM posts p JOIN clubs c ON c.id=p.club_id WHERE p.id=? AND p.status='published' AND c.status='active'", [$pid])) { http_response_code(404); echo '{"ok":false}'; exit; }
if (val('SELECT 1 FROM post_likes WHERE post_id=? AND user_id=?', [$pid, $uid])) { q('DELETE FROM post_likes WHERE post_id=? AND user_id=?', [$pid, $uid]); $liked = false; }
else { q('INSERT INTO post_likes (post_id,user_id) VALUES (?,?)', [$pid, $uid]); $liked = true; }
echo json_encode(['ok' => true, 'liked' => $liked, 'count' => (int) val('SELECT COUNT(*) FROM post_likes WHERE post_id=?', [$pid])]);

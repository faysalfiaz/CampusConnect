<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!is_post() || !user() || user()['role'] !== 'student') { http_response_code(403); echo '{"ok":false}'; exit; }
csrf_check();
$pid = (int) post('post_id'); $uid = user()['id'];
if (!val("SELECT id FROM posts WHERE id=? AND status='published'", [$pid])) { http_response_code(404); echo '{"ok":false}'; exit; }
if (val('SELECT 1 FROM post_saves WHERE post_id=? AND user_id=?', [$pid, $uid])) { q('DELETE FROM post_saves WHERE post_id=? AND user_id=?', [$pid, $uid]); $s = false; }
else { q('INSERT INTO post_saves (post_id,user_id) VALUES (?,?)', [$pid, $uid]); $s = true; }
echo json_encode(['ok' => true, 'saved' => $s]);

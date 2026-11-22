<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_post() || !user() || user()['role'] !== 'student') { http_response_code(403); exit('Forbidden'); }
csrf_check();
$uid = user()['id']; $a = post('action');
if ($a === 'add') {
    $pid = (int) post('post_id'); $body = mb_substr(post('body'), 0, 1000);
    if ($body === '') flash('error', 'Comment cannot be empty.');
    elseif (!val("SELECT id FROM posts WHERE id=? AND status='published'", [$pid])) flash('error', 'Post not available.');
    else { q('INSERT INTO post_comments (post_id,user_id,body) VALUES (?,?,?)', [$pid, $uid, $body]); log_activity('comment_added', 'post ' . $pid); }
} elseif ($a === 'edit') {
    $body = mb_substr(post('body'), 0, 1000);
    if ($body !== '') { q("UPDATE post_comments SET body=? WHERE id=? AND user_id=? AND status='visible'", [$body, (int) post('comment_id'), $uid]); flash('success', 'Comment updated.'); }
} elseif ($a === 'delete') {
    q('DELETE FROM post_comments WHERE id=? AND user_id=?', [(int) post('comment_id'), $uid]); flash('success', 'Comment deleted.');
} elseif ($a === 'report') {
    $cid = (int) post('comment_id'); $reason = mb_substr(post('reason'), 0, 255);
    if ($reason !== '' && val('SELECT id FROM post_comments WHERE id=? AND user_id<>?', [$cid, $uid])) {
        q('INSERT IGNORE INTO post_reports (comment_id, reporter_user_id, reason) VALUES (?,?,?)', [$cid, $uid, $reason]);
        notify_admins('Comment reported', $reason, 'admin/comments.php'); flash('success', 'Report sent to the admin.');
    }
}
redirect_back('student/feed.php');

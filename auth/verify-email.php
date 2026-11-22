<?php
require_once __DIR__ . '/../includes/auth.php';
$token = get('token'); $ok = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $v = row('SELECT * FROM email_verifications WHERE token_hash=? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token)]);
    if ($v) {
        q('UPDATE email_verifications SET used_at=NOW() WHERE id=?', [$v['id']]);
        q('UPDATE users SET email_verified_at=NOW() WHERE id=?', [$v['user_id']]);
        log_activity('email_verified', '', (int) $v['user_id']); $ok = true;
    }
}
flash($ok ? 'success' : 'error', $ok ? 'Email verified. You can log in now.' : 'Invalid or expired verification link.');
redirect('auth/student-login.php');

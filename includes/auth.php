<?php
require_once __DIR__ . '/functions.php';

/** Returns an error string, or null on success. Same message for unknown user / bad password. */
function attempt_login(string $role, string $email, string $password): ?string {
    $u = row('SELECT * FROM users WHERE email=? AND role=?', [strtolower($email), $role]);
    if (!$u) { password_verify($password, '$2y$10$usesomesillystringforsaltuQ4n1Y8y1i9b1v1w1x1y1z1A1B1C1D1E1F'); return 'Invalid email or password.'; }
    if (!password_verify($password, $u['password_hash'])) return 'Invalid email or password.';
    if ($u['status'] !== 'active') return 'This account is disabled. Contact the university admin.';
    if ($role === 'club' && !val("SELECT status FROM clubs WHERE user_id=?", [$u['id']])) return 'Club profile missing.';
    if ($role === 'club' && val("SELECT status FROM clubs WHERE user_id=?", [$u['id']]) !== 'active') return 'This club is disabled.';
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int) $u['id'], 'role' => $u['role'], 'email' => $u['email']];
    q('UPDATE users SET failed_attempts=0, locked_until=NULL, last_login_at=NOW() WHERE id=?', [$u['id']]);
    log_activity('login', $role, (int) $u['id']);
    return null;
}

/** Server-side gate for every protected page. */
function require_role(string $role): void {
    $u = user();
    if (!$u) redirect('auth/' . $role . '-login.php');
    if ($u['role'] !== $role) { http_response_code(403); exit('403 Forbidden — this area is not available for your account type.'); }
    // re-check account still active on every request
    $st = val('SELECT status FROM users WHERE id=?', [$u['id']]);
    if ($st !== 'active') { logout_user(); redirect('auth/' . $role . '-login.php'); }
}
function require_login(): void { if (!user()) redirect('index.php'); }
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']); }
    session_destroy();
}

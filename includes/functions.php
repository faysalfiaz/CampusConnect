<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('ccp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS'])
    ]);
    session_start();
}

/* ---------- DB shortcuts (all PDO prepared) ---------- */
function q(string $sql, array $p = []): PDOStatement {
    $s = db()->prepare($sql);
    $s->execute($p);
    return $s;
}
function row(string $sql, array $p = []) {
    return q($sql, $p)->fetch();
}
function rows(string $sql, array $p = []): array {
    return q($sql, $p)->fetchAll();
}
function val(string $sql, array $p = []) {
    return q($sql, $p)->fetchColumn();
}
function insert_id(): int {
    return (int) db()->lastInsertId();
}

/* ---------- Output / routing ---------- */
function e($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function url(string $p = ''): string {
    return BASE_URL . '/' . ltrim($p, '/');
}
function redirect(string $p): void {
    header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p)));
    exit;
}
function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
function redirect_back(string $fallback): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $h = parse_url($ref, PHP_URL_HOST);
    $mine = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    if ($ref && $h === $mine) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}
function home_for(string $role): string {
    return $role . '/dashboard.php';
}
function dt($s, $f = 'd M Y, h:i A'): string {
    return $s ? date($f, strtotime($s)) : '—';
}
function setting(string $k, $d = '') {
    $v = val('SELECT v FROM settings WHERE k=?', [$k]);
    return $v === false ? $d : $v;
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back and reload the page.');
    }
}

/* ---------- Flash toasts ---------- */
function flash(?string $type = null, ?string $msg = null): array {
    if ($type === null) {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }
    $_SESSION['flash'][] = [$type, $msg];
    return [];
}

/* ---------- Session user ---------- */
function user(): ?array {
    return $_SESSION['user'] ?? null;
}
function current_student(): ?array {
    static $s = false;
    if ($s === false) {
        $s = (user() && user()['role'] === 'student')
            ? row('SELECT s.*, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.user_id=?', [user()['id']])
            : null;
    }
    return $s;
}
function current_club(): ?array {
    static $c = false;
    if ($c === false) {
        $c = (user() && user()['role'] === 'club')
            ? row('SELECT * FROM clubs WHERE user_id=?', [user()['id']])
            : null;
    }
    return $c;
}

/* ---------- Logging ---------- */
function log_activity(string $action, string $details = '', ?int $uid = null): void {
    $u = user();
    q(
        'INSERT INTO activity_logs (user_id, role, action, details, ip) VALUES (?,?,?,?,?)',
        [$uid ?? ($u['id'] ?? null), $u['role'] ?? null, $action, mb_substr($details, 0, 500), $_SERVER['REMOTE_ADDR'] ?? null]
    );
}

/* ---------- Input helpers ---------- */
function post(string $k, string $d = ''): string {
    return trim((string) ($_POST[$k] ?? $d));
}
function get(string $k, string $d = ''): string {
    return trim((string) ($_GET[$k] ?? $d));
}
function valid_password(string $p): bool {
    return $p !== '';
}

/* ---------- Secure upload (images only by default) ---------- */
function upload_file(string $field, string $subdir, bool $imagesOnly = true): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Try again.');
    }
    if ($f['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('File too large (max 5 MB).');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];
    if (!$imagesOnly) {
        $map['application/pdf'] = 'pdf';
    }
    if (!isset($map[$mime])) {
        throw new RuntimeException('Invalid file type.');
    }
    if ($imagesOnly && !@getimagesize($f['tmp_name'])) {
        throw new RuntimeException('Not a valid image.');
    }
    $dir = __DIR__ . '/../assets/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $map[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save file.');
    }
    return 'assets/uploads/' . $subdir . '/' . $name;
}

/* ---------- Ticket / certificate codes ---------- */
function make_code(string $prefix): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(5)));
}

/* ---------- Event phase (derived from dates) ---------- */
function event_phase(array $e): string {
    if ($e['status'] !== 'approved') {
        return ucfirst($e['status']);
    }
    $now = time();
    $start = strtotime($e['event_date'] . ' ' . $e['start_time']);
    $end = strtotime($e['event_date'] . ' ' . $e['end_time']);
    if ($now > $end) {
        return 'Completed';
    }
    if ($now >= $start) {
        return 'Ongoing';
    }
    if ($now <= strtotime($e['registration_deadline'])) {
        return 'Registration Open';
    }
    return 'Registration Closed';
}
function status_badge(string $s): string {
    $c = [
        'draft' => 'secondary',
        'pending' => 'warning text-dark',
        'approved' => 'success',
        'published' => 'success',
        'rejected' => 'danger',
        'archived' => 'dark',
        'cancelled' => 'danger',
        'Registration Open' => 'success',
        'Ongoing' => 'primary',
        'Completed' => 'secondary',
        'Registration Closed' => 'warning text-dark'
    ][$s] ?? 'secondary';
    return '<span class="badge bg-' . $c . '">' . e(ucfirst($s)) . '</span>';
}
function reg_count(int $eventId): int {
    return (int) val("SELECT COUNT(*) FROM event_registrations WHERE event_id=? AND status='registered'", [$eventId]);
}

/* ---------- Ticket + waiting list ---------- */
function issue_ticket(int $regId): void {
    if (!val('SELECT id FROM event_tickets WHERE registration_id=?', [$regId])) {
        q('INSERT INTO event_tickets (registration_id, ticket_code) VALUES (?,?)', [$regId, make_code('CCP')]);
    }
}
function promote_waitlist(int $eventId): void {
    $ev = row('SELECT * FROM events WHERE id=?', [$eventId]);
    if (!$ev || !$ev['max_participants']) {
        return;
    }
    while (reg_count($eventId) < (int) $ev['max_participants']) {
        $w = row("SELECT * FROM event_registrations WHERE event_id=? AND status='waitlist' ORDER BY created_at LIMIT 1", [$eventId]);
        if (!$w) {
            break;
        }
        q("UPDATE event_registrations SET status='registered' WHERE id=?", [$w['id']]);
        issue_ticket((int) $w['id']);
        notify((int) $w['user_id'], 'You got a spot!', 'A seat opened up for ' . $ev['title'] . '. Your ticket is ready.', 'student/tickets.php');
    }
}

/* ---------- Feed query (published & approved posts) ---------- */
function feed_posts(string $where = '', array $params = [], int $uid = 0, int $limit = 30): array {
    $sql = "SELECT p.*, c.name club_name, c.logo club_logo, c.org_type club_type, c.id club_id_ref,
      (SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id) likes,
      (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='visible') comments,
      EXISTS(SELECT 1 FROM post_likes l WHERE l.post_id=p.id AND l.user_id=?) liked,
      EXISTS(SELECT 1 FROM post_saves s WHERE s.post_id=p.id AND s.user_id=?) saved
      FROM posts p JOIN clubs c ON c.id=p.club_id
      WHERE p.status IN ('published', 'approved') AND c.status='active' $where
      ORDER BY p.published_at DESC, p.id DESC LIMIT " . (int) $limit;
    return rows($sql, array_merge([$uid, $uid], $params));
}

require_once __DIR__ . '/notifications.php';
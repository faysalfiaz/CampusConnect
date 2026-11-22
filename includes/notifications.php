<?php
/* In-app notifications + optional email via PHPMailer */
function notify(int $userId, string $title, string $body = '', string $link = ''): void {
    q('INSERT INTO notifications (user_id, title, body, link) VALUES (?,?,?,?)', [$userId, mb_substr($title, 0, 200), mb_substr($body, 0, 500), $link ?: null]);
}
function notify_admins(string $title, string $body = '', string $link = ''): void {
    foreach (rows("SELECT id FROM users WHERE role='admin' AND status='active'") as $a) notify((int) $a['id'], $title, $body, $link);
}
function notify_all_students(string $title, string $body = '', string $link = ''): void {
    q("INSERT INTO notifications (user_id, title, body, link) SELECT id, ?, ?, ? FROM users WHERE role='student' AND status='active'", [$title, mb_substr($body, 0, 500), $link ?: null]);
}
/** Returns true ONLY if an email was really handed to the SMTP server. */
function send_mail(string $to, string $subject, string $html): bool {
    require_once __DIR__ . '/../config/mail.php';
    if (!MAIL_ENABLED) return false;
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoload)) require_once $autoload;
    else {
        $base = __DIR__ . '/../lib/PHPMailer/src/';
        if (!is_file($base . 'PHPMailer.php')) return false;
        require_once $base . 'Exception.php'; require_once $base . 'PHPMailer.php'; require_once $base . 'SMTP.php';
    }
    try {
        $m = new PHPMailer\PHPMailer\PHPMailer(true);
        $m->isSMTP(); $m->Host = MAIL_HOST; $m->SMTPAuth = true; $m->Username = MAIL_USER; $m->Password = MAIL_PASS;
        $m->SMTPSecure = 'tls'; $m->Port = MAIL_PORT; $m->CharSet = 'UTF-8';
        $m->setFrom(MAIL_FROM, MAIL_FROM_NAME); $m->addAddress($to);
        $m->isHTML(true); $m->Subject = $subject; $m->Body = $html;
        $m->send(); return true;
    } catch (Throwable $e) { return false; }
}

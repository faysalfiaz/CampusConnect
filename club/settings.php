<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $uid = user()['id']; $err = [];
if (is_post()) {
    csrf_check();
    if (post('action') === 'password') {
        $u = row('SELECT password_hash FROM users WHERE id=?', [$uid]);
        if (!password_verify((string) $_POST['current'], $u['password_hash'])) $err[] = 'Current password is wrong.';
        elseif (!valid_password((string) $_POST['new'])) $err[] = 'New password cannot be empty.';
        else { q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]); log_activity('password_changed'); flash('success', 'Password changed.'); redirect('club/settings.php'); }
    } elseif (post('action') === 'email_change') {
        $new = strtolower(post('email'));
        if (!filter_var($new, FILTER_VALIDATE_EMAIL)) $err[] = 'Enter a valid email.';
        elseif (val('SELECT id FROM clubs WHERE official_email=?', [$new]) || val("SELECT id FROM users WHERE email=? AND role='club'", [$new])) $err[] = 'That email is already used by a club.';
        elseif (val("SELECT id FROM club_emails WHERE club_id=? AND status='pending'", [$cid])) $err[] = 'You already have a pending request.';
        else { q("INSERT INTO club_emails (club_id, email, status, note) VALUES (?,?, 'pending', ?)", [$cid, $new, post('note') ?: null]); notify_admins('Club email change requested', $c['name'] . ' → ' . $new, 'admin/club-emails.php'); log_activity('email_change_requested', $new); flash('success', 'Request sent. The admin must approve it.'); redirect('club/settings.php'); }
    }
}
$hist = rows('SELECT * FROM club_emails WHERE club_id=? ORDER BY requested_at DESC', [$cid]);
$portal = 'club'; $title = 'Settings';
require __DIR__ . '/../includes/header.php';
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>'; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card shadow-sm"><div class="card-body"><h6>Change password</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="password">
 <div class="col-md-6"><input type="password" name="current" class="form-control" placeholder="Current" required></div><div class="col-md-6"><input type="password" name="new" class="form-control" placeholder="New" required></div><div class="col-12"><button class="btn btn-accent">Update</button></div></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm"><div class="card-body"><h6>Official email</h6><p class="small">Current: <strong><?= e($c['official_email']) ?></strong>. Changes need admin approval.</p>
 <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="email_change"><div class="col-12"><input type="email" name="email" class="form-control" placeholder="New official email" required></div><div class="col-12"><input name="note" class="form-control" placeholder="Reason (optional)"></div><div class="col-12"><button class="btn btn-outline-secondary">Request change</button></div></form>
 <ul class="list-group list-group-flush mt-3 small"><?php foreach ($hist as $h): ?><li class="list-group-item"><?= e($h['email']) ?> — <?= e($h['status']) ?> <span class="text-muted"><?= dt($h['requested_at']) ?></span></li><?php endforeach; ?></ul></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

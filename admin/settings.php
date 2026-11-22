<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$uid = user()['id'];
if (is_post()) {
    csrf_check();
    if (post('action') === 'system') {
        q('REPLACE INTO settings (k,v) VALUES (?,?)', ['allow_student_registration', isset($_POST['allow_reg']) ? '1' : '0']);
        q('REPLACE INTO settings (k,v) VALUES (?,?)', ['university_email_domain', ltrim(post('domain'), '@')]);
        log_activity('settings_updated'); flash('success', 'Settings saved.');
    } elseif (post('action') === 'password') {
        $u = row('SELECT password_hash FROM users WHERE id=?', [$uid]);
        if (!password_verify((string) $_POST['current'], $u['password_hash'])) flash('error', 'Current password is wrong.');
        elseif (!valid_password((string) $_POST['new'])) flash('error', 'New password cannot be empty.');
        else { q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]); log_activity('admin_password_changed'); flash('success', 'Password changed.'); }
    }
    redirect('admin/settings.php');
}
$portal = 'admin'; $title = 'System Settings';
require __DIR__ . '/../includes/header.php'; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card shadow-sm"><div class="card-body"><h6>Registration</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="system">
<div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="allow_reg" id="ar" <?= setting('allow_student_registration', '1') === '1' ? 'checked' : '' ?>><label for="ar" class="form-check-label">Allow new student registrations</label></div>
<label class="form-label">Required university email domain (blank = any)</label><input name="domain" class="form-control mb-3" value="<?= e(setting('university_email_domain')) ?>" placeholder="e.g. ulab.edu.bd"><button class="btn btn-accent">Save</button></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm"><div class="card-body"><h6>Change admin password</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="password"><div class="col-md-6"><input type="password" name="current" class="form-control" placeholder="Current" required></div><div class="col-md-6"><input type="password" name="new" class="form-control" placeholder="New (10+ chars)" required></div><div class="col-12"><button class="btn btn-outline-secondary">Update</button></div></form></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

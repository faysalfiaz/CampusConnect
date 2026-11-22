<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $s = current_student(); $err = [];
if (is_post()) {
    csrf_check();
    if (post('action') === 'profile') {
        $name = post('full_name'); $dept = post('department'); $batch = post('batch'); $sem = post('semester');
        if (!$name || !$dept || !$batch || !$sem) $err[] = 'All fields except phone are required.';
        try { $av = upload_file('avatar', 'avatars'); } catch (RuntimeException $ex) { $err[] = $ex->getMessage(); $av = null; }
        if (!$err) { q('UPDATE students SET full_name=?, department=?, batch=?, semester=?, phone=?, avatar=COALESCE(?,avatar) WHERE user_id=?', [$name, $dept, $batch, $sem, post('phone') ?: null, $av, $uid]); log_activity('profile_updated'); flash('success', 'Profile updated.'); redirect('student/profile.php'); }
    } elseif (post('action') === 'password') {
        $u = row('SELECT password_hash FROM users WHERE id=?', [$uid]);
        if (!password_verify((string) ($_POST['current'] ?? ''), $u['password_hash'])) $err[] = 'Current password is wrong.';
        elseif (!valid_password((string) $_POST['new'])) $err[] = 'New password cannot be empty.';
        else { q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]); log_activity('password_changed'); flash('success', 'Password changed.'); redirect('student/profile.php'); }
    }
}
$acts = rows('SELECT action, details, created_at FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 20', [$uid]);
$portal = 'student'; $title = 'Profile & Activity';
require __DIR__ . '/../includes/header.php';
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>'; ?>
<div class="row g-4"><div class="col-lg-6"><div class="card shadow-sm"><div class="card-body">
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
 <div class="col-12"><label class="form-label">Email</label><input class="form-control" value="<?= e($s['email']) ?>" disabled></div>
 <div class="col-md-6"><label class="form-label">Student ID</label><input class="form-control" value="<?= e($s['student_code']) ?>" disabled></div>
 <div class="col-md-6"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($s['full_name']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Department</label><input name="department" class="form-control" value="<?= e($s['department']) ?>" required></div>
 <div class="col-md-3"><label class="form-label">Batch</label><input name="batch" class="form-control" value="<?= e($s['batch']) ?>" required></div>
 <div class="col-md-3"><label class="form-label">Semester</label><input name="semester" class="form-control" value="<?= e($s['semester']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($s['phone']) ?>"></div>
 <div class="col-md-6"><label class="form-label">Profile picture</label><input type="file" name="avatar" accept="image/*" class="form-control"></div>
 <div class="col-12"><button class="btn btn-accent">Save</button></div></form></div></div>
<div class="card shadow-sm mt-3"><div class="card-body"><h6>Change password</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="password">
 <div class="col-md-6"><input type="password" name="current" class="form-control" placeholder="Current" required></div><div class="col-md-6"><input type="password" name="new" class="form-control" placeholder="New" required></div>
 <div class="col-12"><button class="btn btn-outline-secondary">Update password</button></div></form></div></div></div>
<div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">Recent activity</div><ul class="list-group list-group-flush small">
<?php foreach ($acts as $a): ?><li class="list-group-item"><?= e(str_replace('_', ' ', $a['action'])) ?> <span class="text-muted"><?= e($a['details']) ?> · <?= dt($a['created_at']) ?></span></li><?php endforeach; if (!$acts) echo '<li class="list-group-item text-muted">No activity yet.</li>'; ?></ul></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

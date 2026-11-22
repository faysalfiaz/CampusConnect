<?php
// ONE-TIME admin provisioning. Works only while no admin exists. DELETE this file afterwards.
require_once __DIR__ . '/includes/functions.php';
$exists = (int) val("SELECT COUNT(*) FROM users WHERE role='admin'");
$msg = '';
if (!$exists && is_post()) {
    csrf_check();
    $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = 'Enter a valid email.';
    elseif (!valid_password($pw)) $msg = 'Password cannot be empty.';
    else {
        q("INSERT INTO users (role,email,password_hash,email_verified_at) VALUES ('admin',?,?,NOW())", [$email, password_hash($pw, PASSWORD_DEFAULT)]);
        log_activity('admin_provisioned', $email);
        flash('success', 'Admin created. Now DELETE setup-admin.php and log in.');
        redirect('auth/admin-login.php');
    }
}
$portal = 'public'; $title = 'Initial Admin Setup'; $theme = 'admin';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5"><div class="card auth-card shadow-sm"><div class="card-body p-4">
<h4>Initial admin setup</h4>
<?php if ($exists): ?>
  <div class="alert alert-warning">An admin already exists. Delete this file (<code>setup-admin.php</code>) from the server.</div>
<?php else: ?>
  <?php if ($msg): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Admin email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-accent w-100">Create admin</button>
  </form>
<?php endif; ?>
</div></div></div></div>
<?php require __DIR__ . '/includes/footer.php';

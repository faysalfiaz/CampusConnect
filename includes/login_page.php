<?php
// Shared sign-in screen. Set $role before including ($uiRole is used by the Department screen).
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auth_ui.php';
$uiRole = $uiRole ?? $role;
if (user() && user()['role'] === $role) redirect(home_for($role));
$error = null;
if (is_post()) {
    csrf_check();
    $error = attempt_login($role, post('email'), (string) ($_POST['password'] ?? ''));
    if (!$error) redirect(home_for($role));
}
$titles = ['student' => 'Student Login', 'club' => 'Club Login', 'department' => 'Department Login', 'admin' => 'Admin Login'];
$portal = 'public'; $title = $titles[$uiRole]; $theme = $uiRole;
$wide = true; require __DIR__ . '/header.php';
auth_open($uiRole, 'login', 'Welcome back', 'Sign in with the email and password you registered with.');
?>
  <?php if (get('registered') && !$error): ?><div class="alert alert-success py-2"><i class="bi bi-check-circle-fill"></i> Account created. Now sign in with your email and password.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="text" name="email" class="form-control form-control-lg" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" placeholder="Type your email"></div>
    <div class="mb-4"><label class="form-label">Password</label><input type="password" name="password" class="form-control form-control-lg" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')"></div>
    <button class="btn btn-accent btn-lg w-100">Sign in</button>
  </form>
  <p class="text-center small mt-3 mb-0 text-muted">New here? <a href="<?= url('auth/' . $uiRole . '-register.php') ?>">Create your account first</a></p>
<?php auth_close($uiRole, 'login'); require __DIR__ . '/footer.php';
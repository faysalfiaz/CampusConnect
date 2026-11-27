<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_ui.php';
$allowed = ['student', 'club', 'department', 'admin'];
$asked = is_post() ? post('ui_role') : get('role');
$uiRole = in_array($asked, $allowed, true) ? $asked : 'student';
$role = $uiRole === 'department' ? 'club' : $uiRole;
$proof = [
    'student'    => ['student_code' => 'Student ID', 'phone' => 'Phone number'],
    'club'       => ['name' => 'Club name'],
    'department' => ['name' => 'Department name'],
    'admin'      => ['access_code' => 'Admin access code'],
][$uiRole];
$errors = [];
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email')); $new = (string) ($_POST['new_password'] ?? '');
    $in = []; foreach ($proof as $k => $lbl) $in[$k] = post($k);
    $u = row('SELECT * FROM users WHERE email=? AND role=?', [$email, $role]);
    $ok = false;
    if ($u && $email !== '' && $new !== '' && !in_array('', $in, true)) {
        if ($role === 'student') {
            $s = row('SELECT * FROM students WHERE user_id=?', [$u['id']]);
            $digits = fn($x) => preg_replace('/\D/', '', (string) $x);
            $ok = $s && strcasecmp((string) $s['student_code'], $in['student_code']) === 0
                  && $digits($s['phone']) !== '' && $digits($s['phone']) === $digits($in['phone']);
        } elseif ($role === 'club') {
            $c = row('SELECT * FROM clubs WHERE user_id=?', [$u['id']]);
            $ok = $c && ($c['org_type'] ?? 'club') === ($uiRole === 'department' ? 'department' : 'club')
                  && strcasecmp((string) $c['name'], $in['name']) === 0;
        } else {
            $ok = ADMIN_SIGNUP_CODE !== '' && hash_equals(ADMIN_SIGNUP_CODE, (string) $in['access_code']);
        }
    }
    if ($ok) {
        q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        log_activity('password_reset', $email, (int) $u['id']);
        redirect('auth/' . $uiRole . '-login.php?reset=1');
    }
    $errors[] = 'The details do not match an account. Check them and try again.';
}
$portal = 'public'; $title = 'Reset password'; $theme = $uiRole;
$wide = true; require __DIR__ . '/../includes/header.php';
auth_open($uiRole, 'login', 'Reset your password', 'Type the details you used when you registered, then choose a new password.');
?>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2 mb-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="ui_role" value="<?= e($uiRole) ?>">
    <div class="mb-3"><label class="form-label">Email</label><input type="text" name="email" class="form-control" required autocomplete="off" value="<?= e($_POST['email'] ?? '') ?>"></div>
    <?php foreach ($proof as $k => $lbl): ?>
      <div class="mb-3"><label class="form-label"><?= e($lbl) ?></label><input type="text" name="<?= e($k) ?>" class="form-control" required autocomplete="off"></div>
    <?php endforeach; ?>
    <div class="mb-4"><label class="form-label">New password</label><input type="password" name="new_password" class="form-control" required autocomplete="new-password"></div>
    <button class="btn btn-accent btn-lg w-100">Set new password</button>
  </form>
<?php auth_close($uiRole, 'login'); require __DIR__ . '/../includes/footer.php';
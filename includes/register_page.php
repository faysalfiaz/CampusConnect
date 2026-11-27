<?php
// Shared create-account screen for student, club, department and admin. Set $role before including.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auth_ui.php';
$uiRole = $uiRole ?? $role; $orgType = $orgType ?? 'club';
if (user() && user()['role'] === $role) redirect(home_for($role));
if ($role === 'student' && setting('allow_student_registration', '1') !== '1') { exit('Student registration is currently closed.'); }
$fields = [
    'student' => ['full_name' => 'Full name', 'student_code' => 'Student ID', 'email' => 'Email', 'department' => 'Department', 'batch' => 'Batch', 'semester' => 'Semester', 'phone' => 'Phone'],
    'club'    => ['name' => 'Club name', 'category' => 'Category', 'email' => 'Club email', 'description' => 'Short description'],
    'admin'   => ['email' => 'Email'],
][$role];
if ($uiRole === 'department') { $fields['name'] = 'Department name'; $fields['category'] = 'Faculty / Category'; $fields['email'] = 'Department email'; }
if ($role === 'admin' && ADMIN_SIGNUP_CODE !== '') $fields['access_code'] = 'Admin access code';
$cols = ['full_name' => 6, 'student_code' => 6, 'department' => 6, 'batch' => 3, 'semester' => 3, 'phone' => 6];
$errors = []; $f = [];
if (is_post()) {
    csrf_check();
    foreach ($fields as $k => $lbl) $f[$k] = post($k);
    $f['email'] = strtolower($f['email']); $pw = (string) ($_POST['password'] ?? '');
    // Only rule: every box must be filled. No email, ID or domain checking.
    foreach ($fields as $k => $lbl) if ($f[$k] === '') { $errors[] = 'Please fill in every box.'; break; }
    if ($pw === '' && !$errors) $errors[] = 'Please fill in every box.';
    if (!$errors && val('SELECT id FROM users WHERE email=? AND role=?', [$f['email'], $role])) $errors[] = 'This email is already registered. Try signing in.';
    if (!$errors && $role === 'student' && val('SELECT id FROM students WHERE student_code=?', [$f['student_code']])) $errors[] = 'This Student ID is already used.';
    if (!$errors && $role === 'club' && val('SELECT id FROM clubs WHERE name=? OR official_email=?', [$f['name'], $f['email']])) $errors[] = 'An account with this name or email already exists.';
    if (!$errors && $role === 'admin' && ADMIN_SIGNUP_CODE !== '' && !hash_equals(ADMIN_SIGNUP_CODE, (string) $f['access_code'])) $errors[] = 'Wrong admin access code.';
    $avatar = null;
    if (!$errors && $role === 'student') { try { $avatar = upload_file('avatar', 'avatars'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); } }
    if (!$errors) {
        db()->beginTransaction();
        try {
            q('INSERT INTO users (role,email,password_hash,email_verified_at) VALUES (?,?,?,NOW())', [$role, $f['email'], password_hash($pw, PASSWORD_DEFAULT)]);
            $uid = insert_id();
            if ($role === 'student') q('INSERT INTO students (user_id,full_name,student_code,department,batch,semester,phone,avatar) VALUES (?,?,?,?,?,?,?,?)',
                [$uid, $f['full_name'], $f['student_code'], $f['department'], $f['batch'], $f['semester'], $f['phone'], $avatar]);
            if ($role === 'club') q('INSERT INTO clubs (user_id,name,category,description,official_email,org_type) VALUES (?,?,?,?,?,?)',
                [$uid, $f['name'], $f['category'], $f['description'], $f['email'], $orgType]);
            db()->commit();
        } catch (Throwable $ex) { db()->rollBack(); $errors[] = 'Registration failed. Please try again.' . (APP_DEBUG ? ' (' . $ex->getMessage() . ')' : ''); }
        if (!$errors) {
            log_activity($uiRole . '_registered', $f['email'], $uid);
            redirect('auth/' . $uiRole . '-login.php?registered=1');
        }
    }
}
$portal = 'public'; $title = 'Create ' . $uiRole . ' account'; $theme = $uiRole;
$wide = true; require __DIR__ . '/header.php';
auth_open($uiRole, 'register', 'Create your account', 'Fill in every box. After that you will sign in with the same email and password.', $role === 'student' ? 'auth-wide' : '');
?>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2 mb-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="row g-3" novalidate autocomplete="off">
    <?= csrf_field() ?>
    <?php foreach ($fields as $k => $lbl): ?>
      <div class="col-md-<?= $cols[$k] ?? 12 ?> <?= isset($cols[$k]) && $cols[$k] < 12 ? 'col-' . ($cols[$k] === 3 ? 6 : 12) : '' ?>"><label class="form-label"><?= e($lbl) ?></label>
        <?php if ($k === 'description'): ?><textarea name="description" rows="2" class="form-control" required><?= e($f[$k] ?? '') ?></textarea>
        <?php else: ?><input type="text" name="<?= e($k) ?>" autocomplete="off" class="form-control" value="<?= $k === 'access_code' ? '' : e($f[$k] ?? '') ?>" required><?php endif; ?></div>
    <?php endforeach; ?>
    <div class="col-md-<?= $role === 'student' ? 6 : 12 ?>"><label class="form-label">Password</label><input type="password" name="password" autocomplete="new-password" class="form-control" required></div>
    <?php if ($role === 'student'): ?><div class="col-12"><label class="form-label small mb-1">Profile picture (optional)</label><input type="file" name="avatar" accept="image/*" class="form-control"></div><?php endif; ?>
    <div class="col-12"><button class="btn btn-accent btn-lg w-100">Create account</button></div>
  </form>
<?php auth_close($uiRole, 'register'); require __DIR__ . '/footer.php';
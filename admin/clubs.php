<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$err = [];
if (is_post()) {
    csrf_check(); $a = post('action');
    if ($a === 'create') {
        $name = post('name'); $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Club name and a valid official email are required.';
        elseif (!valid_password($pw)) $err[] = 'Temporary password cannot be empty.';
        elseif (val('SELECT id FROM clubs WHERE name=? OR official_email=?', [$name, $email])) $err[] = 'A club with that name or email already exists.';
        else {
            db()->beginTransaction();
            try {
                q("INSERT INTO users (role,email,password_hash,email_verified_at) VALUES ('club',?,?,NOW())", [$email, password_hash($pw, PASSWORD_DEFAULT)]); $uid = insert_id();
                q('INSERT INTO clubs (user_id,name,category,official_email) VALUES (?,?,?,?)', [$uid, $name, post('category') ?: null, $email]); $cid = insert_id();
                q("INSERT INTO club_emails (club_id,email,status,decided_at,note) VALUES (?,?, 'verified', NOW(), 'Registered by admin')", [$cid, $email]);
                db()->commit(); log_activity('club_created', $name); flash('success', 'Club created. Share the temporary password securely and ask them to change it.'); redirect('admin/clubs.php');
            } catch (PDOException $ex) { db()->rollBack(); $err[] = 'Could not create club.'; }
        }
    } elseif ($a === 'toggle') {
        $c = row('SELECT * FROM clubs WHERE id=?', [(int) post('id')]);
        if ($c) { $ns = $c['status'] === 'active' ? 'disabled' : 'active'; q('UPDATE clubs SET status=? WHERE id=?', [$ns, $c['id']]); q('UPDATE users SET status=? WHERE id=?', [$ns, $c['user_id']]); log_activity('club_' . $ns, $c['name']); flash('success', 'Club ' . $ns . '.'); }
        redirect('admin/clubs.php');
    } elseif ($a === 'reset') {
        $c = row('SELECT * FROM clubs WHERE id=?', [(int) post('id')]); $pw = (string) ($_POST['password'] ?? '');
        if ($c && valid_password($pw)) { q('UPDATE users SET password_hash=?, failed_attempts=0, locked_until=NULL WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $c['user_id']]); log_activity('club_password_reset', $c['name']); flash('success', 'Password reset.'); }
        else flash('error', 'Password cannot be empty.');
        redirect('admin/clubs.php');
    }
}
$clubs = rows('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.club_id=c.id) posts, (SELECT COUNT(*) FROM club_members m WHERE m.club_id=c.id) members FROM clubs c ORDER BY c.name');
$portal = 'admin'; $title = 'Clubs';
require __DIR__ . '/../includes/header.php';
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>'; ?>
<div class="card shadow-sm mb-4"><div class="card-body"><h6>Register a new club</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="create">
 <div class="col-md-3"><input name="name" class="form-control" placeholder="Club name" required></div><div class="col-md-2"><input name="category" class="form-control" placeholder="Category"></div>
 <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Official club email" required></div><div class="col-md-2"><input type="text" name="password" class="form-control" placeholder="Temp password" required></div><div class="col-md-2"><button class="btn btn-accent w-100">Create</button></div></form></div></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Club</th><th>Official email</th><th>Posts</th><th>Members</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($clubs as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= e($c['official_email']) ?></td><td><?= (int) $c['posts'] ?></td><td><?= (int) $c['members'] ?></td><td><?= $c['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></td>
<td class="d-flex gap-1"><form method="post" data-confirm="Change status?"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form>
<form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input name="password" class="form-control form-control-sm" placeholder="New password" style="width:130px" required><button class="btn btn-sm btn-outline-danger">Reset</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php';

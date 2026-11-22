<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
if (is_post()) {
    csrf_check(); $u = row("SELECT id, status, email FROM users WHERE id=? AND role='student'", [(int) post('id')]);
    if ($u && post('action') === 'toggle') { $ns = $u['status'] === 'active' ? 'disabled' : 'active'; q('UPDATE users SET status=? WHERE id=?', [$ns, $u['id']]); log_activity('student_' . $ns, $u['email']); flash('success', 'Student ' . $ns . '.'); }
    elseif ($u && post('action') === 'verify') { q('UPDATE users SET email_verified_at=NOW() WHERE id=?', [$u['id']]); log_activity('student_verified_by_admin', $u['email']); flash('success', 'Email marked verified.'); }
    redirect('admin/students.php?q=' . urlencode(get('q')));
}
$qs = get('q');
$list = rows("SELECT s.*, u.email, u.status, u.email_verified_at FROM students s JOIN users u ON u.id=s.user_id WHERE s.full_name LIKE ? OR s.student_code LIKE ? OR u.email LIKE ? OR s.department LIKE ? ORDER BY s.created_at DESC LIMIT 300", array_fill(0, 4, "%$qs%"));
$portal = 'admin'; $title = 'Students';
require __DIR__ . '/../includes/header.php'; ?>
<form class="d-flex gap-2 mb-3"><input name="q" class="form-control" placeholder="Search name, ID, email, department" value="<?= e($qs) ?>"><button class="btn btn-accent">Search</button></form>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Dept / Batch</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $s): ?><tr><td><?= e($s['student_code']) ?></td><td><?= e($s['full_name']) ?></td><td><?= e($s['email']) ?><?= $s['email_verified_at'] ? '' : ' <span class="badge bg-warning text-dark">unverified</span>' ?></td><td><?= e($s['department']) ?> / <?= e($s['batch']) ?></td>
<td><?= $s['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></td>
<td class="d-flex gap-1"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $s['user_id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $s['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form>
<?php if (!$s['email_verified_at']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="id" value="<?= (int) $s['user_id'] ?>"><button class="btn btn-sm btn-outline-primary">Verify</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="6" class="empty">No students found.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php';

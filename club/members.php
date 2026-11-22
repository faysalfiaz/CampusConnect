<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id'];
if (is_post()) {
    csrf_check();
    if (post('action') === 'add') {
        $n = post('full_name');
        if ($n === '') flash('error', 'Name is required.');
        else { q("INSERT INTO club_members (club_id, full_name, student_code, position) VALUES (?,?,?, 'General Member')", [$cid, $n, post('student_code') ?: null]); log_activity('member_added', $n); flash('success', 'Member added.'); }
    } elseif (post('action') === 'delete') {
        q('DELETE FROM club_members WHERE id=? AND club_id=?', [(int) post('id'), $cid]); flash('success', 'Member removed.');
    }
    redirect('club/members.php');
}
$members = rows('SELECT * FROM club_members WHERE club_id=? ORDER BY full_name', [$cid]);
$portal = 'club'; $title = 'Members';
require __DIR__ . '/../includes/header.php'; ?>
<form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="action" value="add">
 <div class="col-md-4"><input name="full_name" class="form-control" placeholder="Full name" required></div><div class="col-md-3"><input name="student_code" class="form-control" placeholder="Student ID (optional)"></div>
 <div class="col-md-2"><button class="btn btn-accent w-100">Add member</button></div></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Student ID</th><th>Position</th><th></th></tr></thead><tbody>
<?php foreach ($members as $m): ?><tr><td><?= e($m['full_name']) ?></td><td><?= e($m['student_code']) ?></td><td><?= e($m['position']) ?></td>
<td><form method="post" data-confirm="Remove this member?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr><?php endforeach; ?>
<?php if (!$members): ?><tr><td colspan="4" class="empty"><i class="bi bi-people"></i>No members yet.</td></tr><?php endif; ?></tbody></table></div>
<p class="small text-muted">Assign executive positions and posting permission on the <a href="<?= url('club/executives.php') ?>">Executives</a> page.</p>
<?php require __DIR__ . '/../includes/footer.php';

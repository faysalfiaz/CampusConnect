<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id'];
$positions = ['President', 'Vice President', 'General Secretary', 'Joint Secretary', 'Event Executive', 'Media Executive', 'Finance Executive', 'General Member'];
if (is_post()) {
    csrf_check();
    $pos = post('position');
    if (!in_array($pos, $positions, true)) { flash('error', 'Invalid position.'); redirect('club/executives.php'); }
    if ($pos === 'President' && val("SELECT COUNT(*) FROM club_members WHERE club_id=? AND position='President' AND id<>?", [$cid, (int) post('id')])) { flash('error', 'This club already has a President.'); redirect('club/executives.php'); }
    // Presidents always may post; General Members never get posting rights automatically
    $can = $pos === 'General Member' ? 0 : (isset($_POST['can_post']) || $pos === 'President' ? 1 : 0);
    q('UPDATE club_members SET position=?, can_post=? WHERE id=? AND club_id=?', [$pos, $can, (int) post('id'), $cid]);
    log_activity('executive_updated', $pos); flash('success', 'Updated.'); redirect('club/executives.php');
}
$members = rows("SELECT * FROM club_members WHERE club_id=? ORDER BY FIELD(position,'President','Vice President','General Secretary','Joint Secretary','Event Executive','Media Executive','Finance Executive','General Member'), full_name", [$cid]);
$portal = 'club'; $title = 'Executive Committee';
require __DIR__ . '/../includes/header.php'; ?>
<p class="text-muted small">Only executives marked “can post” may be credited on posts and events. General Members never get executive rights automatically. Add people first on the <a href="<?= url('club/members.php') ?>">Members</a> page.</p>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Position</th><th>Can create posts/events</th><th></th></tr></thead><tbody>
<?php foreach ($members as $m): ?><tr><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
<td><?= e($m['full_name']) ?></td>
<td><select name="position" class="form-select form-select-sm"><?php foreach ($positions as $p): ?><option <?= $m['position'] === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select></td>
<td><input type="checkbox" name="can_post" class="form-check-input" <?= $m['can_post'] ? 'checked' : '' ?>></td><td><button class="btn btn-sm btn-accent">Save</button></td></form></tr><?php endforeach; ?>
<?php if (!$members): ?><tr><td colspan="4" class="empty">Add members first.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php';

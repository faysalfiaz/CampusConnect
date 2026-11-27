<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('club');

if (!function_exists('Settings')) {
    function Settings($key = null, $default = null) {
        return $default;
    }
}

$c = current_club() ?: [];
$cid = (int) ($c['id'] ?? 0);
$isDept = (($c['org_type'] ?? 'club') === 'department');

$positions = [
    'President',
    'Vice President',
    'General Secretary',
    'Joint Secretary',
    'Event Executive',
    'Media Executive',
    'Finance Executive',
    'General Member',
];

$deptLabels = [
    'President' => 'Head of Department',
    'Vice President' => 'Deputy Head',
    'General Secretary' => 'Coordinator',
    'Joint Secretary' => 'Assistant Coordinator',
    'Event Executive' => 'Event Coordinator',
    'Media Executive' => 'Media Coordinator',
    'Finance Executive' => 'Finance Officer',
    'General Member' => 'Faculty / Staff',
];

$label = fn($p) => $isDept ? ($deptLabels[$p] ?? $p) : $p;

if (is_post()) {
    csrf_check();
    $action = post('action');

    if ($action === 'add') {
        $n = post('full_name');
        $pos = post('position') ?: 'General Member';
        if ($n === '') {
            flash('error', 'Name is required.');
        } elseif (!in_array($pos, $positions, true)) {
            flash('error', 'Invalid position.');
        } elseif ($pos === 'President' && (int) val("SELECT COUNT(*) FROM club_members WHERE club_id=? AND position='President'", [$cid]) > 0) {
            flash('error', 'A ' . $label('President') . ' already exists.');
        } else {
            $can = $pos === 'General Member' ? 0 : ((isset($_POST['can_post']) || $pos === 'President') ? 1 : 0);
            q(
                'INSERT INTO club_members (club_id, full_name, student_code, position, can_post) VALUES (?,?,?,?,?)',
                [$cid, $n, $isDept ? null : (post('student_code') ?: null), $pos, $can]
            );
            log_activity('member_added', $n);
            flash('success', 'Person added.' . ($can ? ' They can create posts and events.' : ''));
        }
    } elseif ($action === 'delete') {
        q('DELETE FROM club_members WHERE id=? AND club_id=?', [(int) post('id'), $cid]);
        flash('success', 'Removed.');
    }

    redirect('club/members.php');
}

$members = rows('SELECT * FROM club_members WHERE club_id=? ORDER BY full_name', [$cid]);
$portal = 'club';
$title = 'Members';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="row g-2 mb-3 align-items-center">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="add">
  <div class="col-md-3">
    <input name="full_name" class="form-control" placeholder="<?= e($isDept ? 'Teacher / staff name' : 'Full name') ?>" required>
  </div>
  <?php if (!$isDept): ?>
    <div class="col-md-2">
      <input name="student_code" class="form-control" placeholder="Student ID (optional)">
    </div>
  <?php endif; ?>
  <div class="col-md-3">
    <select name="position" class="form-select">
      <?php foreach ($positions as $p): ?>
        <option value="<?= e($p) ?>" <?= $p === 'General Member' ? 'selected' : '' ?>><?= e($label($p)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="can_post" id="cp">
      <label class="form-check-label" for="cp">Can post</label>
    </div>
  </div>
  <div class="col-md-2">
    <button class="btn btn-accent w-100">Add</button>
  </div>
</form>

<p class="small text-muted">"Can post" lets this person be credited on posts and events. General members never get it.</p>

<div class="table-responsive">
  <table class="table align-middle">
    <thead>
      <tr>
        <th>Name</th>
        <?php if (!$isDept): ?><th>Student ID</th><?php endif; ?>
        <th>Position</th>
        <th>Can post</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $m): ?>
        <tr>
          <td><?= e($m['full_name']) ?></td>
          <?php if (!$isDept): ?><td><?= e($m['student_code']) ?></td><?php endif; ?>
          <td><?= e($label($m['position'] ?? 'General Member')) ?></td>
          <td><?= !empty($m['can_post']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
          <td>
            <form method="post" data-confirm="Remove this person?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$members): ?>
        <tr><td colspan="<?= $isDept ? 4 : 5 ?>" class="empty"><i class="bi bi-people"></i>No one added yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<p class="small text-muted">To change a position or posting permission later, use the <a href="<?= e(url('club/executives.php')) ?>">Executives</a> page.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('club');

if (!function_exists('Settings')) {
    function Settings($key = null, $default = null) {
        return $default;
    }
}

function h($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$c = current_club();
$cid = (int) ($c['id'] ?? 0);
$isDept = (($c['org_type'] ?? 'club') === 'department');

$roleOptions = $isDept
    ? [
        'President' => 'Head of Department',
        'Vice President' => 'Deputy Head',
        'General Secretary' => 'Coordinator',
        'Joint Secretary' => 'Assistant Coordinator',
        'Event Executive' => 'Event Coordinator',
        'Media Executive' => 'Media Coordinator',
        'Finance Executive' => 'Finance Officer',
        'General Member' => 'Faculty / Staff',
    ]
    : [
        'President' => 'President',
        'Vice President' => 'Vice President',
        'General Secretary' => 'General Secretary',
        'Joint Secretary' => 'Joint Secretary',
        'Event Executive' => 'Event Executive',
        'Media Executive' => 'Media Executive',
        'Finance Executive' => 'Finance Executive',
        'General Member' => 'General Member',
    ];

$generalLabel = $roleOptions['General Member'];

if (is_post()) {
    csrf_check();
    $action = post('action');

    if ($action === 'delete') {
        $mid = (int) post('id');
        q('DELETE FROM club_members WHERE id=? AND club_id=?', [$mid, $cid]);
        log_activity('member_removed', 'Member ID #' . $mid);
        flash('success', 'Removed.');
        redirect('club/executives.php');
    }

    $pos = post('position');
    if (!array_key_exists($pos, $roleOptions)) {
        flash('error', 'Invalid position.');
        redirect('club/executives.php');
    }

    $isAdd = ($action === 'add');
    $mid = $isAdd ? 0 : (int) post('id');

    if ($pos === 'President') {
        $exists = (int) val(
            "SELECT COUNT(*) FROM club_members WHERE club_id=? AND position='President' AND id<>?",
            [$cid, $mid]
        );
        if ($exists > 0) {
            flash('error', 'A ' . $roleOptions['President'] . ' already exists.');
            redirect('club/executives.php');
        }
    }

    $can = ($pos === 'General Member') ? 0 : ((isset($_POST['can_post']) || $pos === 'President') ? 1 : 0);

    if ($isAdd) {
        $n = trim((string) post('full_name'));
        if ($n === '') {
            flash('error', 'Name is required.');
            redirect('club/executives.php');
        }
        q(
            'INSERT INTO club_members (club_id, full_name, student_code, position, can_post) VALUES (?,?,?,?,?)',
            [$cid, $n, null, $pos, $can]
        );
        log_activity('member_added', $n);
        flash('success', $n . ' added.' . ($can ? ' They can now create posts and events.' : ''));
    } else {
        q(
            'UPDATE club_members SET position=?, can_post=? WHERE id=? AND club_id=?',
            [$pos, $can, $mid, $cid]
        );
        log_activity('executive_updated', $pos);
        flash('success', 'Updated.');
    }

    redirect('club/executives.php');
}

$members = rows(
    "SELECT * FROM club_members WHERE club_id=? ORDER BY FIELD(position,'President','Vice President','General Secretary','Joint Secretary','Event Executive','Media Executive','Finance Executive','General Member'), full_name",
    [$cid]
);

$portal = 'club';
$title = 'Executive Committee';
require __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-person-plus"></i> Add a person who can post</div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-center">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">

      <div class="col-md-4">
        <input type="text" name="full_name" class="form-control" placeholder="<?= h($isDept ? 'Teacher / staff name' : 'Member name') ?>" required>
      </div>

      <div class="col-md-3">
        <select name="position" class="form-select" style="color: #fff; background-color: #1e2230;">
          <?php foreach ($roleOptions as $key => $text): ?>
            <option value="<?= h($key) ?>" <?= ($key === 'Event Executive') ? 'selected' : '' ?> style="color: #fff; background-color: #1e2230;">
              <?= h($text) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="can_post" id="cp" checked>
          <label class="form-check-label" for="cp">Can create posts/events</label>
        </div>
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-accent w-100">Add</button>
      </div>
    </form>
    <div class="form-text">
      The person you add here is the one credited when posts or events are created. "<?= h($generalLabel) ?>" never gets posting rights.
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table align-middle">
    <thead>
      <tr>
        <th>Name</th>
        <th>Position</th>
        <th>Can create posts/events</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $m): ?>
        <?php $formId = 'upd_' . (int) $m['id']; ?>
        <tr>
          <td><?= h($m['full_name']) ?></td>
          <td>
            <select name="position" form="<?= h($formId) ?>" class="form-select form-select-sm" style="color: #fff; background-color: #1e2230;">
              <?php foreach ($roleOptions as $key => $text): ?>
                <option value="<?= h($key) ?>" <?= (($m['position'] ?? '') === $key) ? 'selected' : '' ?> style="color: #fff; background-color: #1e2230;">
                  <?= h($text) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input type="checkbox" name="can_post" form="<?= h($formId) ?>" class="form-check-input" <?= !empty($m['can_post']) ? 'checked' : '' ?>>
          </td>
          <td class="d-flex gap-1">
            <form id="<?= h($formId) ?>" method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button type="submit" class="btn btn-sm btn-accent">Save</button>
            </form>
            <form method="post" class="d-inline" data-confirm="Remove this person?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$members): ?>
        <tr>
          <td colspan="4" class="empty">No one added yet. Use the form above.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
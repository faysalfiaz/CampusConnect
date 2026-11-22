<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $uid = user()['id'];
$id = (int) get('id'); $ev = null; $err = [];
if ($id) {
    $ev = row('SELECT * FROM events WHERE id=? AND club_id=?', [$id, $cid]);
    if (!$ev) { http_response_code(404); exit('Event not found.'); }
    if (!in_array($ev['status'], ['draft', 'rejected', 'pending'], true)) { flash('error', 'Approved or cancelled events cannot be edited. Cancel and create a new one if the plan changed.'); redirect('club/events.php'); }
}
$hasExec = (int) val('SELECT COUNT(*) FROM club_members WHERE club_id=? AND can_post=1', [$cid]);
$venues = rows('SELECT name FROM venues ORDER BY name');
if (is_post()) {
    csrf_check(); $mode = post('mode');
    $f = []; foreach (['title', 'description', 'category', 'event_date', 'start_time', 'end_time', 'venue', 'registration_deadline', 'rules', 'requirements', 'eligible_dept'] as $k) $f[$k] = post($k);
    $max = post('max_participants') !== '' ? (int) post('max_participants') : null;
    foreach (['title', 'description', 'event_date', 'start_time', 'end_time', 'venue', 'registration_deadline'] as $k) if ($f[$k] === '') $err[] = ucfirst(str_replace('_', ' ', $k)) . ' is required.';
    if (!$err) {
        if ($f['end_time'] <= $f['start_time']) $err[] = 'End time must be after start time.';
        if (strtotime($f['registration_deadline']) > strtotime($f['event_date'] . ' ' . $f['end_time'])) $err[] = 'Registration deadline must be before the event ends.';
        if ($mode === 'submit' && strtotime($f['event_date'] . ' ' . $f['start_time']) < time()) $err[] = 'The event start must be in the future.';
        if ($max !== null && $max < 1) $err[] = 'Maximum participants must be at least 1.';
        if ($mode === 'submit' && !$hasExec) $err[] = 'An executive with posting permission is required to submit events (Executives page).';
        // conflict detection: same venue, same date, overlapping time (approved or pending events of any club)
        if (!$err && $mode === 'submit' && val("SELECT COUNT(*) FROM events WHERE venue=? AND event_date=? AND status IN ('approved','pending') AND id<>? AND start_time < ? AND end_time > ?", [$f['venue'], $f['event_date'], $id, $f['end_time'], $f['start_time']]))
            $err[] = 'Venue conflict: that venue is already booked or requested for an overlapping time. Pick another time or venue.';
    }
    try { $poster = upload_file('poster', 'events'); } catch (RuntimeException $ex) { $err[] = $ex->getMessage(); }
    if (!$err) {
        $poster = $poster ?? ($ev['poster'] ?? null);
        $status = $mode === 'submit' ? 'pending' : ($ev['status'] ?? 'draft');
        if ($mode === 'draft' && $ev && $ev['status'] === 'rejected') $status = 'rejected';
        $vals = [$f['title'], $f['description'], $poster, $f['category'] ?: null, $f['event_date'], $f['start_time'], $f['end_time'], $f['venue'], $max, str_replace('T', ' ', $f['registration_deadline']), $f['rules'] ?: null, $f['requirements'] ?: null, $f['eligible_dept'] ?: null, $status];
        if (!$ev) { q('INSERT INTO events (title,description,poster,category,event_date,start_time,end_time,venue,max_participants,registration_deadline,rules,requirements,eligible_dept,status,club_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$cid])); $eid = insert_id(); $act = 'submitted'; }
        else { q('UPDATE events SET title=?,description=?,poster=?,category=?,event_date=?,start_time=?,end_time=?,venue=?,max_participants=?,registration_deadline=?,rules=?,requirements=?,eligible_dept=?,status=?, rejection_reason=IF(?='pending',NULL,rejection_reason) WHERE id=? AND club_id=?', array_merge($vals, [$status, $id, $cid])); $eid = $id; $act = $ev['status'] === 'rejected' ? 'resubmitted' : 'submitted'; }
        if ($mode === 'submit' && (!$ev || $ev['status'] !== 'pending')) { q('INSERT INTO event_approvals (event_id, action, actor_user_id) VALUES (?,?,?)', [$eid, $act, $uid]); notify_admins('Event ' . $act, $c['name'] . ': ' . $f['title'], 'admin/events.php'); }
        log_activity('event_' . $status, $f['title']);
        flash('success', $mode === 'submit' ? 'Event submitted for admin approval.' : 'Draft saved.'); redirect('club/events.php');
    }
}
$v = fn($k) => e($_POST[$k] ?? ($ev[$k] ?? ''));
$dl = $_POST['registration_deadline'] ?? ($ev ? date('Y-m-d\TH:i', strtotime($ev['registration_deadline'])) : '');
$portal = 'club'; $title = $ev ? 'Edit Event' : 'Create Event';
require __DIR__ . '/../includes/header.php';
if ($ev && $ev['status'] === 'rejected'): ?><div class="alert alert-danger"><strong>Rejected by admin:</strong> <?= e($ev['rejection_reason']) ?></div><?php endif;
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>'; ?>
<div class="card shadow-sm"><div class="card-body"><form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
 <div class="col-md-8"><label class="form-label">Event title *</label><input name="title" class="form-control" value="<?= $v('title') ?>" required></div>
 <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= $v('category') ?>" placeholder="Workshop, Seminar, Contest…"></div>
 <div class="col-12"><label class="form-label">Description *</label><textarea name="description" rows="4" class="form-control" required><?= $v('description') ?></textarea></div>
 <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="event_date" class="form-control" value="<?= $v('event_date') ?>" required></div>
 <div class="col-md-4"><label class="form-label">Start time *</label><input type="time" name="start_time" class="form-control" value="<?= e(substr($_POST['start_time'] ?? ($ev['start_time'] ?? ''), 0, 5)) ?>" required></div>
 <div class="col-md-4"><label class="form-label">End time *</label><input type="time" name="end_time" class="form-control" value="<?= e(substr($_POST['end_time'] ?? ($ev['end_time'] ?? ''), 0, 5)) ?>" required></div>
 <div class="col-md-4"><label class="form-label">Venue *</label><input name="venue" list="venueList" class="form-control" value="<?= $v('venue') ?>" required><datalist id="venueList"><?php foreach ($venues as $vn): ?><option value="<?= e($vn['name']) ?>"><?php endforeach; ?></datalist></div>
 <div class="col-md-4"><label class="form-label">Registration deadline *</label><input type="datetime-local" name="registration_deadline" class="form-control" value="<?= e($dl) ?>" required></div>
 <div class="col-md-2"><label class="form-label">Max participants</label><input type="number" min="1" name="max_participants" class="form-control" value="<?= $v('max_participants') ?>" placeholder="No limit"></div>
 <div class="col-md-2"><label class="form-label">Only for dept.</label><input name="eligible_dept" class="form-control" value="<?= $v('eligible_dept') ?>" placeholder="Any"></div>
 <div class="col-md-6"><label class="form-label">Event rules</label><textarea name="rules" rows="3" class="form-control"><?= $v('rules') ?></textarea></div>
 <div class="col-md-6"><label class="form-label">Registration requirements</label><textarea name="requirements" rows="3" class="form-control"><?= $v('requirements') ?></textarea></div>
 <div class="col-md-6"><label class="form-label">Poster</label><input type="file" name="poster" accept="image/*" class="form-control"></div>
 <div class="col-12 d-flex gap-2"><?php if (!$ev || $ev['status'] !== 'pending'): ?><button name="mode" value="draft" class="btn btn-outline-primary">Save as draft</button><?php endif; ?><button name="mode" value="submit" class="btn btn-accent"><?= $ev && $ev['status'] === 'rejected' ? 'Resubmit for approval' : 'Submit for approval' ?></button></div>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
if (is_post()) {
    csrf_check();
    if (post('action') === 'add') {
        if (post('name') === '') flash('error', 'Name required.');
        else { try { q('INSERT INTO venues (name, location, capacity) VALUES (?,?,?)', [post('name'), post('location') ?: null, post('capacity') !== '' ? (int) post('capacity') : null]); flash('success', 'Venue added.'); } catch (PDOException $e) { flash('error', 'Venue already exists.'); } }
    } elseif (post('action') === 'delete') { q('DELETE FROM venues WHERE id=?', [(int) post('id')]); flash('success', 'Venue removed.'); }
    redirect('admin/venues.php');
}
$venues = rows('SELECT * FROM venues ORDER BY name');
$bookings = rows("SELECT e.venue, e.title, e.event_date, e.start_time, e.end_time, c.name club_name, e.status FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.status IN ('approved','pending') AND e.event_date >= CURDATE() ORDER BY e.event_date, e.start_time LIMIT 50");
$portal = 'admin'; $title = 'Venues & Bookings';
require __DIR__ . '/../includes/header.php'; ?>
<form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="action" value="add"><div class="col-md-3"><input name="name" class="form-control" placeholder="Venue name" required></div><div class="col-md-3"><input name="location" class="form-control" placeholder="Location"></div><div class="col-md-2"><input type="number" name="capacity" class="form-control" placeholder="Capacity"></div><div class="col-md-2"><button class="btn btn-accent">Add</button></div></form>
<div class="row g-3"><div class="col-lg-5"><ul class="list-group"><?php foreach ($venues as $v): ?><li class="list-group-item d-flex justify-content-between"><span><?= e($v['name']) ?> <span class="small text-muted"><?= e($v['location']) ?> <?= $v['capacity'] ? '· cap ' . (int) $v['capacity'] : '' ?></span></span><form method="post" data-confirm="Remove venue?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $v['id'] ?>"><button class="btn btn-sm btn-outline-danger">×</button></form></li><?php endforeach; ?></ul></div>
<div class="col-lg-7"><div class="card"><div class="card-header">Upcoming bookings (approved & pending events)</div><ul class="list-group list-group-flush small"><?php foreach ($bookings as $b): ?><li class="list-group-item"><strong><?= e($b['venue']) ?></strong> — <?= e($b['title']) ?> (<?= e($b['club_name']) ?>) · <?= dt($b['event_date'], 'd M') ?> <?= dt($b['start_time'], 'h:i A') ?>–<?= dt($b['end_time'], 'h:i A') ?> <?= status_badge($b['status']) ?></li><?php endforeach; if (!$bookings) echo '<li class="list-group-item text-muted">None.</li>'; ?></ul></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

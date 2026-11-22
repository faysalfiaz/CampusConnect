<?php
require_once __DIR__ . '/includes/functions.php';
$code = strtoupper(get('code')); $c = null;
if ($code) $c = row('SELECT ce.*, e.title, e.event_date, cl.name club_name, s.full_name FROM certificates ce JOIN events e ON e.id=ce.event_id JOIN clubs cl ON cl.id=e.club_id JOIN students s ON s.user_id=ce.user_id WHERE ce.cert_code=?', [$code]);
$portal = 'public'; $title = 'Verify Certificate'; $theme = 'student';
require __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-6"><form class="d-flex gap-2 mb-3"><input name="code" class="form-control" placeholder="Certificate ID (CERT-XXXXXXXXXX)" value="<?= e($code) ?>"><button class="btn btn-accent">Verify</button></form>
<?php if ($code && !$c): ?><div class="alert alert-danger">No certificate found with this ID.</div>
<?php elseif ($c): ?><div class="alert alert-success"><strong>Certificate is genuine</strong><br>Awarded to <strong><?= e($c['full_name']) ?></strong><br>Event: <?= e($c['title']) ?> (<?= e($c['club_name']) ?>)<br>Issued: <?= dt($c['issued_at'], 'd M Y') ?></div><?php endif; ?></div></div>
<?php require __DIR__ . '/includes/footer.php';

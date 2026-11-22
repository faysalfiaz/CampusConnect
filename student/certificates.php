<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$certs = rows('SELECT ce.*, e.title, e.event_date, c.name club_name FROM certificates ce JOIN events e ON e.id=ce.event_id JOIN clubs c ON c.id=e.club_id WHERE ce.user_id=? ORDER BY ce.issued_at DESC', [user()['id']]);
$portal = 'student'; $title = 'My Certificates';
require __DIR__ . '/../includes/header.php'; ?>
<?php if (!$certs): ?><div class="empty"><i class="bi bi-award"></i>No certificates yet. Attend an event and your club may issue one.</div><?php endif; ?>
<div class="list-group"><?php foreach ($certs as $c): ?>
<div class="list-group-item d-flex justify-content-between align-items-center"><div><strong><?= e($c['title']) ?></strong><div class="small text-muted"><?= e($c['club_name']) ?> · issued <?= dt($c['issued_at'], 'd M Y') ?> · <code><?= e($c['cert_code']) ?></code></div></div>
<a class="btn btn-sm btn-accent" target="_blank" href="<?= url('certificate.php?code=' . urlencode($c['cert_code'])) ?>"><i class="bi bi-download"></i> View / Download</a></div>
<?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php';

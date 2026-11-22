<?php
require_once __DIR__ . '/includes/functions.php';
$code = strtoupper(get('code')); $t = null;
if ($code) $t = row("SELECT t.ticket_code, r.status, e.title, e.event_date, c.name club_name, a.scanned_at, s.full_name FROM event_tickets t JOIN event_registrations r ON r.id=t.registration_id JOIN events e ON e.id=r.event_id JOIN clubs c ON c.id=e.club_id JOIN students s ON s.user_id=r.user_id LEFT JOIN attendance a ON a.ticket_id=t.id WHERE t.ticket_code=?", [$code]);
$portal = 'public'; $title = 'Verify Ticket'; $theme = 'student';
require __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-6"><form class="d-flex gap-2 mb-3"><input name="code" class="form-control" placeholder="Ticket code (CCP-XXXXXXXXXX)" value="<?= e($code) ?>"><button class="btn btn-accent">Check</button></form>
<?php if ($code && !$t): ?><div class="alert alert-danger">Invalid ticket.</div>
<?php elseif ($t): ?><div class="alert alert-<?= $t['status'] === 'registered' ? 'success' : 'warning' ?>"><strong><?= $t['status'] === 'registered' ? 'Valid ticket' : 'Ticket no longer valid' ?></strong><br><?= e($t['title']) ?> · <?= e($t['club_name']) ?> · <?= dt($t['event_date'], 'd M Y') ?><br>Holder: <?= e($t['full_name']) ?><br><?= $t['scanned_at'] ? 'Already used on ' . dt($t['scanned_at']) : 'Not yet used' ?></div><?php endif; ?></div></div>
<?php require __DIR__ . '/includes/footer.php';

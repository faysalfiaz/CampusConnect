<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $s = current_student();
$tickets = rows("SELECT t.*, e.title, e.event_date, e.start_time, e.venue, c.name club_name, a.scanned_at
  FROM event_tickets t JOIN event_registrations r ON r.id=t.registration_id JOIN events e ON e.id=r.event_id JOIN clubs c ON c.id=e.club_id
  LEFT JOIN attendance a ON a.ticket_id=t.id WHERE r.user_id=? AND r.status='registered' ORDER BY e.event_date DESC", [$uid]);
$history = array_filter($tickets, fn($t) => $t['scanned_at']);
$portal = 'student'; $title = 'Tickets & Attendance';
$extra_js = '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>
document.querySelectorAll(".qr").forEach(function(el){ new QRCode(el,{text:el.dataset.code,width:150,height:150}); });
function dl(id){ var c=document.querySelector("#qr-"+id+" canvas"); if(!c) return; var a=document.createElement("a"); a.href=c.toDataURL("image/png"); a.download="ticket-"+id+".png"; a.click(); }
</script>';
require __DIR__ . '/../includes/header.php'; ?>
<?php if (!$tickets): ?><div class="empty"><i class="bi bi-qr-code"></i>No tickets yet. Register for an event to get one.</div><?php endif; ?>
<div class="row g-3">
<?php foreach ($tickets as $t): ?>
<div class="col-md-6 col-xl-4"><div class="ticket p-3 bg-body shadow-sm">
  <div class="small text-muted"><?= e($t['club_name']) ?></div><h6><?= e($t['title']) ?></h6>
  <div class="small mb-2"><?= dt($t['event_date'] . ' ' . $t['start_time']) ?> · <?= e($t['venue']) ?></div>
  <div class="d-flex gap-3 align-items-center"><div class="qr" id="qr-<?= (int) $t['id'] ?>" data-code="<?= e($t['ticket_code']) ?>"></div>
    <div class="small"><strong><?= e($s['full_name']) ?></strong><br>ID: <?= e($s['student_code']) ?><br>Ticket: <code><?= e($t['ticket_code']) ?></code><br>
    <?= $t['scanned_at'] ? '<span class="badge bg-success">Attended</span>' : '<span class="badge bg-secondary">Not scanned</span>' ?></div></div>
  <div class="mt-2 no-print"><button class="btn btn-sm btn-outline-secondary" onclick="dl(<?= (int) $t['id'] ?>)"><i class="bi bi-download"></i> Download QR</button>
  <a class="btn btn-sm btn-outline-secondary" href="<?= url('verify-ticket.php?code=' . urlencode($t['ticket_code'])) ?>" target="_blank">Verify</a></div>
</div></div>
<?php endforeach; ?></div>
<h5 class="mt-4">Attendance history</h5>
<ul class="list-group"><?php foreach ($history as $t): ?><li class="list-group-item"><?= e($t['title']) ?> <span class="small text-muted">· scanned <?= dt($t['scanned_at']) ?></span></li><?php endforeach; if (!$history) echo '<li class="list-group-item text-muted">No attendance recorded yet.</li>'; ?></ul>
<?php require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $id = (int) get('id');
$e = row("SELECT * FROM events WHERE id=? AND club_id=? AND status='approved'", [$id, $cid]);
if (!$e) { http_response_code(404); exit('Event not found.'); }
if (is_post()) {
    csrf_check();
    if (post('action') === 'issue_certs') {
        $att = rows('SELECT r.user_id FROM attendance a JOIN event_tickets t ON t.id=a.ticket_id JOIN event_registrations r ON r.id=t.registration_id WHERE a.event_id=?', [$id]); $n = 0;
        foreach ($att as $a) {
            if (!val('SELECT id FROM certificates WHERE event_id=? AND user_id=?', [$id, $a['user_id']])) {
                q('INSERT INTO certificates (cert_code, event_id, user_id) VALUES (?,?,?)', [make_code('CERT'), $id, $a['user_id']]);
                notify((int) $a['user_id'], 'Certificate issued', 'Your certificate for ' . $e['title'] . ' is ready.', 'student/certificates.php'); $n++;
            }
        }
        log_activity('certificates_issued', $e['title'] . ' x' . $n); flash('success', $n . ' certificate(s) issued.');
    }
    redirect('club/event-manage.php?id=' . $id);
}
if (get('export') === 'csv') {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="attendance-' . $id . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Student ID', 'Name', 'Department', 'Registration', 'Attendance', 'Scanned at']);
    foreach (rows("SELECT s.student_code, s.full_name, s.department, r.status, a.scanned_at FROM event_registrations r JOIN students s ON s.user_id=r.user_id LEFT JOIN event_tickets t ON t.registration_id=r.id LEFT JOIN attendance a ON a.ticket_id=t.id WHERE r.event_id=? AND r.status<>'cancelled' ORDER BY s.full_name", [$id]) as $r)
        fputcsv($o, [$r['student_code'], $r['full_name'], $r['department'], $r['status'], $r['scanned_at'] ? 'Present' : 'Absent', $r['scanned_at']]);
    exit;
}
$list = rows("SELECT s.student_code, s.full_name, s.department, r.status, t.ticket_code, a.scanned_at, (SELECT cert_code FROM certificates ce WHERE ce.event_id=r.event_id AND ce.user_id=r.user_id) cert FROM event_registrations r JOIN students s ON s.user_id=r.user_id LEFT JOIN event_tickets t ON t.registration_id=r.id LEFT JOIN attendance a ON a.ticket_id=t.id WHERE r.event_id=? AND r.status<>'cancelled' ORDER BY r.status, s.full_name", [$id]);
$present = count(array_filter($list, fn($r) => $r['scanned_at'])); $regd = count(array_filter($list, fn($r) => $r['status'] === 'registered'));
$portal = 'club'; $title = $e['title'];
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script><script>
var busy=false;
function send(code){ if(busy||!code) return; busy=true; var fd=new FormData(); fd.append("code",code); fd.append("event_id",' . $id . '); fd.append("csrf",window.CCP.csrf);
 fetch(window.CCP.base+"/api/attendance.php",{method:"POST",body:fd}).then(r=>r.json()).then(function(j){ var b=document.getElementById("scanResult"); b.className="alert alert-"+(j.ok?"success":"danger"); b.textContent=(j.name?j.name+" — ":"")+j.message; if(j.ok) setTimeout(function(){location.reload()},1500); }).finally(function(){ setTimeout(function(){busy=false},1500); }); }
document.getElementById("manualForm").addEventListener("submit",function(ev){ ev.preventDefault(); send(document.getElementById("manualCode").value.trim()); });
var sc=new Html5QrcodeScanner("reader",{fps:10,qrbox:220},false); sc.render(function(t){send(t)},function(){});
</script>';
require __DIR__ . '/../includes/header.php'; ?>
<p><?= status_badge(event_phase($e)) ?> <span class="text-muted"><?= dt($e['event_date'], 'd M Y') ?> · <?= e($e['venue']) ?></span></p>
<div class="row g-3 mb-3"><div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Registered</div><div class="num"><?= $regd ?></div></div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Present</div><div class="num"><?= $present ?></div></div></div></div>
<div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Absent</div><div class="num"><?= max(0, $regd - $present) ?></div></div></div></div></div>
<div class="row g-3"><div class="col-lg-5"><div class="card shadow-sm"><div class="card-header">QR attendance scanner</div><div class="card-body">
 <div id="reader"></div><div id="scanResult" class="mt-2 alert alert-secondary py-2">Waiting for a scan…</div>
 <form id="manualForm" class="d-flex gap-2"><input id="manualCode" class="form-control" placeholder="Or type ticket code"><button class="btn btn-accent">Check in</button></form>
 <div class="form-text">Camera scanning needs HTTPS or localhost.</div></div></div></div>
<div class="col-lg-7"><div class="d-flex gap-2 mb-2 flex-wrap"><a class="btn btn-sm btn-outline-secondary" href="?id=<?= $id ?>&export=csv"><i class="bi bi-filetype-csv"></i> Attendance report (CSV)</a>
 <form method="post" data-confirm="Issue certificates to everyone marked present?"><?= csrf_field() ?><input type="hidden" name="action" value="issue_certs"><button class="btn btn-sm btn-accent"><i class="bi bi-award"></i> Issue certificates to attendees</button></form></div>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>ID</th><th>Name</th><th>Dept</th><th>Status</th><th>Certificate</th></tr></thead><tbody>
<?php foreach ($list as $r): ?><tr><td><?= e($r['student_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['department']) ?></td>
<td><?= $r['status'] === 'waitlist' ? '<span class="badge bg-warning text-dark">Waitlist</span>' : ($r['scanned_at'] ? '<span class="badge bg-success">Present</span>' : '<span class="badge bg-secondary">Absent</span>') ?></td><td class="small"><?= e($r['cert']) ?></td></tr><?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="5" class="empty">No participants yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$byClub = rows("SELECT c.name, COUNT(p.id) n FROM clubs c LEFT JOIN posts p ON p.club_id=c.id AND p.status='published' GROUP BY c.id ORDER BY n DESC LIMIT 10");
$regs = rows("SELECT e.title, COUNT(r.id) n FROM events e LEFT JOIN event_registrations r ON r.event_id=e.id AND r.status='registered' WHERE e.status='approved' GROUP BY e.id ORDER BY n DESC LIMIT 10");
$dept = rows('SELECT department, COUNT(*) n FROM students GROUP BY department ORDER BY n DESC LIMIT 10');
$daily = rows("SELECT DATE(created_at) d, COUNT(*) n FROM event_registrations WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d");
$portal = 'admin'; $title = 'Analytics';
$j = fn($rows, $k) => json_encode(array_map(fn($r) => $r[$k], $rows));
$ji = fn($rows, $k) => json_encode(array_map(fn($r) => (int) $r[$k], $rows));
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>
new Chart(c1,{type:"bar",data:{labels:' . $j($byClub, 'name') . ',datasets:[{label:"Published posts",data:' . $ji($byClub, 'n') . ',backgroundColor:"#be123c"}]}});
new Chart(c2,{type:"bar",data:{labels:' . $j($regs, 'title') . ',datasets:[{label:"Registrations",data:' . $ji($regs, 'n') . ',backgroundColor:"#4f46e5"}]},options:{indexAxis:"y"}});
new Chart(c3,{type:"pie",data:{labels:' . $j($dept, 'department') . ',datasets:[{data:' . $ji($dept, 'n') . '}]}});
new Chart(c4,{type:"line",data:{labels:' . $j($daily, 'd') . ',datasets:[{label:"Registrations / day",data:' . $ji($daily, 'n') . ',borderColor:"#0d9488"}]}});</script>';
require __DIR__ . '/../includes/header.php'; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-header">Published posts by club</div><div class="card-body"><canvas id="c1"></canvas></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Top events by registrations</div><div class="card-body"><canvas id="c2"></canvas></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Students by department</div><div class="card-body"><canvas id="c3"></canvas></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Registrations, last 30 days</div><div class="card-body"><canvas id="c4"></canvas></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';

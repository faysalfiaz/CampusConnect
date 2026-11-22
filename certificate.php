<?php
// Printable certificate. Owner, issuing club or admin may view; anyone can verify via verify-certificate.php.
require_once __DIR__ . '/includes/auth.php'; require_login();
$code = strtoupper(get('code'));
$c = row('SELECT ce.*, e.title, e.event_date, e.club_id, cl.name club_name, cl.logo, s.full_name FROM certificates ce JOIN events e ON e.id=ce.event_id JOIN clubs cl ON cl.id=e.club_id JOIN students s ON s.user_id=ce.user_id WHERE ce.cert_code=?', [$code]);
if (!$c) { http_response_code(404); exit('Certificate not found.'); }
$u = user();
$allowed = $u['role'] === 'admin' || ($u['role'] === 'student' && (int) $c['user_id'] === $u['id']) || ($u['role'] === 'club' && (int) $c['club_id'] === (int) current_club()['id']);
if (!$allowed) { http_response_code(403); exit('Forbidden'); }
$verify = 'http://' . $_SERVER['HTTP_HOST'] . url('verify-certificate.php?code=' . $c['cert_code']);
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Certificate <?= e($c['cert_code']) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=Figtree:wght@400;600&display=swap" rel="stylesheet">
<style>
body{font-family:Figtree,Georgia,sans-serif;background:#e9ecf2;margin:0;padding:24px;color:#172033}
.cert{position:relative;max-width:920px;margin:auto;background:#fffdf8;padding:64px 56px;text-align:center;border:1px solid #d9c9a3;box-shadow:0 30px 60px -30px rgba(23,32,51,.45)}
.cert::before{content:"";position:absolute;inset:14px;border:2px solid #b8892b;pointer-events:none}
.cert::after{content:"";position:absolute;inset:22px;border:1px solid #d9c9a3;pointer-events:none}
.cert h1{font-family:"Cormorant Garamond",Georgia,serif;font-size:54px;letter-spacing:.14em;color:#1f2a44;margin:.2em 0 0;font-weight:700}
.cert .sub{font-family:"Cormorant Garamond",Georgia,serif;font-size:22px;color:#b8892b;letter-spacing:.2em;margin:0 0 28px}
.cert p{margin:.5em 0;color:#4b5565}.cert h2{font-family:"Cormorant Garamond",Georgia,serif;font-size:34px;color:#1f2a44;margin:.3em 0}
.name{font-family:"Cormorant Garamond",Georgia,serif;font-size:48px;font-weight:700;color:#4a47d6;display:inline-block;padding:0 48px 6px;margin:14px 0;border-bottom:2px solid #b8892b}
.meta{display:flex;justify-content:space-between;align-items:flex-end;text-align:left;margin-top:44px;font-size:13px;color:#4b5565;line-height:1.7}
.seal{width:84px;height:84px;border-radius:50%;background:radial-gradient(circle at 30% 30%,#e7c873,#b8892b);margin:0 auto 6px;display:grid;place-items:center;color:#fff;font-size:34px;box-shadow:0 6px 14px -6px rgba(184,137,43,.8)}
.no-print{text-align:center;margin-bottom:16px}.no-print button{background:#4a47d6;color:#fff;border:0;padding:.6rem 1.2rem;border-radius:10px;font-weight:600;cursor:pointer}
@media print{body{background:#fff;padding:0}.no-print{display:none}.cert{box-shadow:none}}
@media(max-width:640px){.cert{padding:40px 20px}.cert h1{font-size:34px}.name{font-size:32px;padding:0 12px}.meta{flex-direction:column;gap:14px;align-items:center;text-align:center}}
</style></head><body>
<div class="no-print"><button onclick="window.print()">Print / Save as PDF</button></div>
<div class="cert"><?php if ($c['logo']): ?><img src="<?= url($c['logo']) ?>" height="70" alt=""><?php endif; ?>
<h1>CERTIFICATE</h1><p class="sub">OF PARTICIPATION</p><p>This is to certify that</p><div class="name"><?= e($c['full_name']) ?></div>
<p>has successfully participated in</p><h2><?= e($c['title']) ?></h2><p>organised by <strong><?= e($c['club_name']) ?></strong> on <?= dt($c['event_date'], 'd F Y') ?></p>
<div class="meta"><div>Certificate ID: <strong><?= e($c['cert_code']) ?></strong><br>Issued: <?= dt($c['issued_at'], 'd M Y') ?><br>Verify at: <?= e(url('verify-certificate.php')) ?></div><div id="qr"></div></div></div>
<script>new QRCode(document.getElementById('qr'),{text:<?= json_encode($verify) ?>,width:100,height:100});</script></body></html>

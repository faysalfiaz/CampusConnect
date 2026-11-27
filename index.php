<?php
require_once __DIR__ . '/includes/functions.php';
$u = user(); if ($u) redirect($u["role"] . "/dashboard.php");
$portal = 'public'; $title = 'Welcome'; $theme = 'student';
$notices = []; try { $notices = rows('SELECT * FROM notices ORDER BY is_emergency DESC, created_at DESC LIMIT 3'); } catch (Throwable $e) {}
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($notices as $n): ?><div class="alert alert-<?= $n['is_emergency'] ? 'danger' : 'info' ?>"><strong><?= $n['is_emergency'] ? 'EMERGENCY: ' : '' ?><?= e($n['title']) ?></strong> — <?= e(mb_strimwidth($n['body'], 0, 160, '…')) ?></div><?php endforeach; ?>
<section class="hero hero-wrap"><div class="aura"></div>
  <div class="row align-items-center g-4">
    <div class="col-lg-6">
      <h1>Campus life, all in one place.</h1>
      <p class="lead mt-3">Find events, register in seconds, and keep your tickets and certificates where you can always find them.</p>
      <div class="big-ticket mt-4 d-none d-lg-block" aria-hidden="true" style="margin-inline:0;max-width:330px"><div class="bt-head"><small>Event ticket</small><h4>Your next event</h4><small>Sample preview</small></div><div class="bt-body"><div class="bt-meta"><div>Name<strong>Student name</strong></div><div>Entry<strong>Scan at the door</strong></div></div><div class="qr-art"><?php for ($k = 0; $k < 81; $k++): ?><i class="<?= (((($k * 7) + intdiv($k, 3)) % 5) < 2) ? 'on' : '' ?>"></i><?php endfor; ?></div></div></div>
      <p class="small mt-4 mb-0"><a href="<?= url('verify-certificate.php') ?>">Verify a certificate</a> &nbsp;|&nbsp; <a href="<?= url('verify-ticket.php') ?>">Verify a ticket</a></p>
    </div>
    <div class="col-lg-6 d-grid gap-3">
      <div class="pass" data-portal="student"><div class="pass-stub"><i class="bi bi-mortarboard"></i></div><div class="pass-body"><h5>Student</h5><p class="text-muted small mb-2">Browse events, get your QR ticket, collect certificates.</p><a class="btn btn-sm btn-accent" href="<?= url('auth/student-register.php') ?>">Create account</a> <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/student-login.php') ?>">Sign in</a></div></div>
      <div class="pass" data-portal="club"><div class="pass-stub"><i class="bi bi-people"></i></div><div class="pass-body"><h5>Club</h5><p class="text-muted small mb-2">Share updates, run events and scan attendance.</p><a class="btn btn-sm btn-accent" href="<?= url('auth/club-register.php') ?>">Create account</a> <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/club-login.php') ?>">Sign in</a></div></div>
      <div class="pass" data-portal="department"><div class="pass-stub"><i class="bi bi-building"></i></div><div class="pass-body"><h5>Department</h5><p class="text-muted small mb-2">Post notices and events, scan attendance and issue certificates.</p><a class="btn btn-sm btn-accent" href="<?= url('auth/department-register.php') ?>">Create account</a> <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/department-login.php') ?>">Sign in</a></div></div>
      <div class="pass" data-portal="admin"><div class="pass-stub"><i class="bi bi-shield-lock"></i></div><div class="pass-body"><h5>Admin</h5><p class="text-muted small mb-2">Review posts, moderate comments and track the numbers.</p><a class="btn btn-sm btn-accent" href="<?= url('auth/admin-register.php') ?>">Create account</a> <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/admin-login.php') ?>">Sign in</a></div></div>
    </div>
  </div>
</section>
<section class="feat-grid mb-4">
  <div class="feat"><i class="bi bi-calendar-event"></i><strong>Discover events</strong><span>Browse by category, check the calendar and register in one tap.</span></div>
  <div class="feat"><i class="bi bi-qr-code"></i><strong>QR tickets</strong><span>Every registration becomes a ticket that is scanned at the door.</span></div>
  <div class="feat"><i class="bi bi-award"></i><strong>Instant certificates</strong><span>Attend an event and your verified certificate appears automatically.</span></div>
  <div class="feat"><i class="bi bi-people"></i><strong>Club hub</strong><span>Clubs post updates, manage members and run events from one place.</span></div>
  <div class="feat"><i class="bi bi-shield-check"></i><strong>Admin review</strong><span>Posts and events are approved before students see them.</span></div>
  <div class="feat"><i class="bi bi-star"></i><strong>Feedback and ratings</strong><span>Students rate events they attended so clubs keep improving.</span></div>
</section>
<section class="cta-band">
  <div><h3>Ready to get started?</h3><p>Create your account first, then sign in any time.</p></div>
  <div class="cta-btns"><a class="btn btn-light" href="<?= url('auth/student-register.php') ?>"><i class="bi bi-mortarboard"></i> I am a student</a><a class="btn btn-outline-light" href="<?= url('auth/club-register.php') ?>"><i class="bi bi-people"></i> I run a club</a><a class="btn btn-outline-light" href="<?= url('auth/admin-register.php') ?>"><i class="bi bi-shield-lock"></i> I am an admin</a></div>
</section>
<?php require __DIR__ . '/includes/footer.php';

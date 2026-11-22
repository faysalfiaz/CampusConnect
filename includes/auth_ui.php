<?php
// Shared frame for sign-in and create-account: info panel on the left, form card on the right.
function auth_open(string $role, string $mode, string $heading, string $sub, string $cls = ''): void {
    $names = ['student' => 'Student', 'club' => 'Club', 'admin' => 'Admin'];
    $info = [
        'student' => ['h' => 'Everything campus, in one place.', 'p' => 'Join events, keep your tickets and collect your certificates without chasing anyone.',
            'f' => [['calendar-event', 'Events', 'Find and join what is happening'], ['qr-code', 'QR tickets', 'Your entry pass, always with you'], ['award', 'Certificates', 'Earned and kept automatically'], ['people', 'Clubs', 'Follow clubs and campus news']],
            's' => ['Create your account', 'Join an event', 'Show your QR at the door']],
        'club' => ['h' => 'Run your club without the chaos.', 'p' => 'Posts, events, attendance and certificates, all managed from one dashboard.',
            'f' => [['megaphone', 'Posts', 'Share updates with students'], ['calendar-plus', 'Events', 'Create and manage with ease'], ['qr-code-scan', 'Attendance', 'Scan QR tickets at the door'], ['award', 'Certificates', 'Issue to everyone who attended']],
            's' => ['Register your club', 'Post an event', 'Scan and certify']],
        'admin' => ['h' => 'Keep the campus running smoothly.', 'p' => 'Review what is waiting, moderate content and watch the numbers.',
            'f' => [['check2-square', 'Approvals', 'Posts and events in one queue'], ['flag', 'Moderation', 'Handle reported comments'], ['graph-up', 'Analytics', 'See activity at a glance'], ['building', 'Clubs', 'Create, edit and manage clubs']],
            's' => ['Create admin account', 'Review requests', 'Track the numbers']],
    ][$role];
    ?>
<div class="auth-stage"><div class="aurora"><i></i><i></i><i></i></div>
<div class="auth-grid <?= e($cls) ?>">
  <section class="auth-info">
    <span class="info-chip"><i class="bi bi-stars"></i> <?= $names[$role] ?> portal</span>
    <h2><?= e($info['h']) ?></h2><p><?= e($info['p']) ?></p>
    <div class="info-cards"><?php foreach ($info['f'] as [$ic, $t, $d]): ?><div class="info-card"><i class="bi bi-<?= $ic ?>"></i><strong><?= e($t) ?></strong><span><?= e($d) ?></span></div><?php endforeach; ?></div>
    <ol class="info-steps"><?php foreach ($info['s'] as $n => $st): ?><li><b><?= $n + 1 ?></b><?= e($st) ?></li><?php endforeach; ?></ol>
  </section>
  <div class="auth-card2">
    <div class="ac-brand"><span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span><div><strong><?= APP_NAME ?></strong><small><?= $names[$role] ?> portal</small></div></div>
    <div class="auth-tabs">
      <a class="<?= $mode === 'register' ? 'active' : '' ?>" href="<?= url('auth/' . $role . '-register.php') ?>"><i class="bi bi-person-plus"></i> Create account</a>
      <a class="<?= $mode === 'login' ? 'active' : '' ?>" href="<?= url('auth/' . $role . '-login.php') ?>"><i class="bi bi-box-arrow-in-right"></i> Sign in</a>
    </div>
    <h4><?= e($heading) ?></h4><p class="text-muted small mb-3"><?= e($sub) ?></p>
<?php }
function auth_close(string $role, string $mode): void { ?>
    <div class="auth-switch">Other portals:
      <?php foreach (['student' => 'Student', 'club' => 'Club', 'admin' => 'Admin'] as $r => $n): if ($r !== $role): ?><a href="<?= url('auth/' . $r . '-' . $mode . '.php') ?>"><?= $n ?></a><?php endif; endforeach; ?>
      <a href="<?= url('index.php') ?>">Home</a></div>
  </div>
</div></div>
<?php }

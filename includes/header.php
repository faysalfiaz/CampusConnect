<?php
// Expects: $portal ('student'|'club'|'admin'|'public'), $title. Optional: $theme.
$u = user();
$theme = $theme ?? $portal;
$menus = [
 'student' => [['student/dashboard.php','speedometer2','Dashboard'],['student/feed.php','newspaper','Feed'],['student/clubs.php','people','Clubs'],['student/events.php','calendar-event','Events'],['student/calendar.php','calendar3','Calendar'],['student/registrations.php','journal-check','My Registrations'],['student/tickets.php','qr-code','Tickets & Attendance'],['student/certificates.php','award','Certificates'],['student/feedback.php','star','My Feedback'],['student/saved.php','bookmark','Saved Posts'],['student/notices.php','megaphone','Notices'],['student/profile.php','person-circle','Profile & Activity']],
 'club' => [['club/dashboard.php','speedometer2','Dashboard'],['club/profile.php','building','Club Profile'],['club/executives.php','award','Executives'],['club/members.php','people','Members'],['club/posts.php','file-earmark-text','Posts'],['club/create-post.php','plus-circle','New Post'],['club/events.php','calendar-event','Events'],['club/create-event.php','calendar-plus','New Event'],['club/approvals.php','clipboard-check','Approvals & Feedback'],['club/feedback.php','star','Event Feedback'],['club/settings.php','gear','Settings']],
 'admin' => [['admin/dashboard.php','speedometer2','Dashboard'],['admin/posts.php','file-earmark-check','Post Approvals'],['admin/events.php','calendar-check','Event Approvals'],['admin/clubs.php','building','Clubs'],['admin/club-emails.php','envelope-check','Club Emails'],['admin/students.php','mortarboard','Students'],['admin/comments.php','chat-dots','Comments & Reports'],['admin/notices.php','megaphone','Notices'],['admin/venues.php','geo-alt','Venues'],['admin/analytics.php','graph-up','Analytics'],['admin/logs.php','clock-history','Activity Logs'],['admin/settings.php','gear','Settings']],
];
$unread = $u ? (int) val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$u['id']]) : 0;
$cur = ltrim(str_replace(BASE_URL, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-portal="<?= e($theme) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? APP_NAME) ?> · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
<script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('ccp-theme') || 'light');</script>
</head>
<body>
<nav class="navbar topbar sticky-top px-3">
  <div class="d-flex align-items-center gap-2">
    <?php if (isset($menus[$portal])): ?><button class="btn btn-sm btn-outline-secondary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button><?php endif; ?>
    <a class="navbar-brand fw-bold mb-0" href="<?= url($u ? home_for($u['role']) : 'index.php') ?>"><span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span> <?= APP_NAME ?></a>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Light / dark"><i class="bi bi-moon-stars"></i></button>
    <?php if ($u): ?>
      <a class="btn btn-sm btn-outline-secondary position-relative" href="<?= url('notifications.php') ?>"><i class="bi bi-bell"></i>
        <?php if ($unread): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread ?></span><?php endif; ?></a>
      <span class="avatar d-none d-sm-grid" title="<?= e($u['email'] ?? '') ?>"><?= e(strtoupper(mb_substr($u['email'] ?? '?', 0, 1))) ?></span>
      <form method="post" action="<?= url('auth/logout.php') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Logout</span></button></form>
    <?php else: ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/student-login.php') ?>">Student</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/club-login.php') ?>">Club</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('auth/admin-login.php') ?>">Admin</a>
    <?php endif; ?>
  </div>
</nav>
<?php if (isset($menus[$portal])): ?>
<div class="d-flex">
  <aside class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-lg-none"><h6 class="offcanvas-title"><?= ucfirst($portal) ?> Portal</h6><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button></div>
    <div class="offcanvas-body flex-column p-0">
      <div class="px-3 py-3 small fw-semibold portal-label"><?= ucfirst($portal) ?> portal</div>
      <ul class="nav flex-column">
        <?php foreach ($menus[$portal] as [$href, $icon, $label]): ?>
          <li class="nav-item"><a class="nav-link <?= $cur === $href ? 'active' : '' ?>" href="<?= url($href) ?>"><i class="bi bi-<?= $icon ?> me-2"></i><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php $tip = ['student' => ['Never miss an event', 'Open the calendar to see what is coming up.', 'student/calendar.php', 'Open calendar'], 'club' => ['Ready to share news?', 'Create a post and send it for review.', 'club/create-post.php', 'New post'], 'admin' => ['Pending work', 'Review posts and events waiting for approval.', 'admin/posts.php', 'Open reviews']][$portal] ?? null; ?>
      <?php if ($tip): ?><div class="side-card"><i class="bi bi-lightbulb"></i><strong><?= e($tip[0]) ?></strong><p><?= e($tip[1]) ?></p><a class="btn btn-sm btn-accent w-100" href="<?= url($tip[2]) ?>"><?= e($tip[3]) ?></a></div><?php endif; ?>
    </div>
  </aside>
  <main class="flex-grow-1 p-3 p-lg-4 content">
    <?php if (empty($noHead)): ?><?php $b = basename($_SERVER['SCRIPT_NAME'], '.php'); $artKey = ['events'=>'cal','calendar'=>'cal','registrations'=>'cal','create-event'=>'cal','event-manage'=>'cal','clubs'=>'people','members'=>'people','executives'=>'people','students'=>'people','profile'=>'people','tickets'=>'ticket','certificates'=>'certificate','feedback'=>'star','feed'=>'mega','posts'=>'mega','create-post'=>'mega','saved'=>'mega','notices'=>'mega','analytics'=>'chart','dashboard'=>'chart','logs'=>'chart','comments'=>'chat','settings'=>'gear','venues'=>'pin','club-emails'=>'mail','approvals'=>'check','post-review'=>'check'][$b] ?? 'campus'; if ($artKey === 'certificate') $artKey = 'award'; ?><div class="page-head art-<?= e($artKey) ?>"><h3><?= e($title) ?></h3><span class="page-date"><i class="bi bi-calendar3"></i> <?= date('l, d F') ?></span></div><?php endif; ?>
<?php else: ?>
<main class="<?= !empty($wide) ? 'container-fluid p-0' : 'container py-4' ?>">
<?php endif; ?>

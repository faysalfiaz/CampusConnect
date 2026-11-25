<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $where = ''; $params = [];
$qstr = get('q'); $club = (int) get('club'); $cat = get('category');
if ($qstr !== '') { $where .= ' AND (p.title LIKE ? OR p.description LIKE ?)'; $params[] = "%$qstr%"; $params[] = "%$qstr%"; }
if ($club) { $where .= ' AND p.club_id=?'; $params[] = $club; }
if ($cat !== '') { $where .= ' AND p.category=?'; $params[] = $cat; }
$posts = feed_posts($where, $params, $uid, 50);
$clubs = rows("SELECT id,name FROM clubs WHERE status='active' ORDER BY name");
$cats = rows("SELECT DISTINCT category FROM posts WHERE status='published' AND category IS NOT NULL AND category<>'' ORDER BY category");
$upc = rows("SELECT id,title,event_date,start_time FROM events WHERE status='approved' AND event_date>=CURDATE() ORDER BY event_date,start_time LIMIT 4");
$topClubs = rows("SELECT c.id,c.name,c.logo,(SELECT COUNT(*) FROM posts p WHERE p.club_id=c.id AND p.status='published') n FROM clubs c WHERE c.status='active' ORDER BY n DESC LIMIT 5");
$portal = 'student'; $title = 'Campus Feed';
require __DIR__ . '/../includes/header.php';
?>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input name="q" class="form-control" placeholder="Search posts…" value="<?= e($qstr) ?>"></div>
  <div class="col-6 col-md-3"><select name="club" class="form-select"><option value="">All clubs</option><?php foreach ($clubs as $c): ?><option value="<?= $c['id'] ?>" <?= $club === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-2"><select name="category" class="form-select"><option value="">Any category</option><?php foreach ($cats as $c): ?><option <?= $cat === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-accent w-100">Filter</button></div>
</form>
<div class="row g-4"><div class="col-xl-8">
<?php foreach ($posts as $p) include __DIR__ . '/../includes/post_card.php'; ?>
<?php if (!$posts): ?><div class="empty"><i class="bi bi-newspaper"></i>No posts found.</div><?php endif; ?>
</div>
<div class="col-xl-4"><div class="side-sticky side-panel">
  <div class="card"><div class="card-header d-flex justify-content-between"><span>Upcoming events</span><a class="small" href="<?= url('student/calendar.php') ?>">Calendar</a></div><div class="list-group list-group-flush">
    <?php foreach ($upc as $u): ?><a class="list-group-item list-group-item-action d-flex gap-3 align-items-center" href="<?= url('student/events.php?id=' . (int) $u['id']) ?>"><span class="mini-date"><b><?= e(dt($u['event_date'], 'd')) ?></b><?= e(dt($u['event_date'], 'M')) ?></span><span><?= e($u['title']) ?><br><small class="text-muted"><?= e(dt($u['event_date'] . ' ' . $u['start_time'], 'h:i A')) ?></small></span></a><?php endforeach; ?>
    <?php if (!$upc): ?><div class="list-group-item text-muted small">No upcoming events yet.</div><?php endif; ?></div></div>
  <div class="card"><div class="card-header">Clubs & Administration</div><div class="list-group list-group-flush">
    <?php foreach ($topClubs as $tc): ?><a class="list-group-item list-group-item-action d-flex gap-3 align-items-center" href="<?= url('student/clubs.php?id=' . (int) $tc['id']) ?>"><?php if ($tc['logo']): ?><img class="club-logo" style="width:36px;height:36px" src="<?= url($tc['logo']) ?>" alt=""><?php else: ?><span class="club-logo" style="width:36px;height:36px"><?= e(mb_substr($tc['name'], 0, 1)) ?></span><?php endif; ?><span><?= e($tc['name']) ?><br><small class="text-muted"><?= (int) $tc['n'] ?> posts</small></span></a><?php endforeach; ?></div></div>
</div></div></div>
<?php include __DIR__ . '/../includes/comment_forms.php'; require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $id = (int) get('id');
$portal = 'student';
if ($id) {
    $c = row("SELECT * FROM clubs WHERE id=? AND status='active'", [$id]);
    if (!$c) { http_response_code(404); exit('Club not found.'); }
    $posts = feed_posts(' AND p.club_id=?', [$id], $uid, 20);
    $events = rows("SELECT * FROM events WHERE club_id=? AND status='approved' ORDER BY event_date DESC LIMIT 10", [$id]);
    $execs = rows("SELECT full_name, position FROM club_members WHERE club_id=? AND position<>'General Member' ORDER BY FIELD(position,'President','Vice President','General Secretary','Joint Secretary','Event Executive','Media Executive','Finance Executive')", [$id]);
    $title = $c['name'];
    require __DIR__ . '/../includes/header.php'; ?>
    <div class="cover mb-3" <?= $c['cover'] ? 'style="background-image:url(' . e(url($c['cover'])) . ')"' : '' ?>></div>
    <p><?= nl2br(e($c['description'] ?: 'No description yet.')) ?></p>
    <p class="small text-muted">Contact: <?= e($c['official_email']) ?><?= $c['category'] ? ' · ' . e($c['category']) : '' ?></p>
    <?php if ($execs): ?><h6>Executive committee</h6><ul class="small"><?php foreach ($execs as $x): ?><li><?= e($x['position']) ?> — <?= e($x['full_name']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <h5 class="mt-4">Events</h5>
    <div class="list-group mb-4"><?php foreach ($events as $e): ?><a class="list-group-item list-group-item-action" href="<?= url('student/events.php?id=' . $e['id']) ?>"><?= e($e['title']) ?> <span class="small text-muted">· <?= dt($e['event_date'], 'd M Y') ?></span> <?= status_badge(event_phase($e)) ?></a><?php endforeach; if (!$events) echo '<div class="list-group-item text-muted">No events.</div>'; ?></div>
    <h5>Posts</h5><div class="row"><div class="col-lg-8"><?php foreach ($posts as $p) include __DIR__ . '/../includes/post_card.php'; if (!$posts) echo '<div class="empty">No posts yet.</div>'; ?></div></div>
    <?php include __DIR__ . '/../includes/comment_forms.php'; require __DIR__ . '/../includes/footer.php'; exit;
}
$qs = get('q');
$clubs = rows("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.club_id=c.id AND p.status='published') post_count FROM clubs c WHERE c.status='active' AND (c.name LIKE ? OR c.category LIKE ?) ORDER BY c.name", ["%$qs%", "%$qs%"]);
$title = 'University Clubs';
require __DIR__ . '/../includes/header.php'; ?>
<form class="mb-3 d-flex gap-2"><input name="q" class="form-control" placeholder="Search clubs…" value="<?= e($qs) ?>"><button class="btn btn-accent">Search</button></form>
<div class="row g-3">
<?php foreach ($clubs as $c): ?>
 <div class="col-md-6 col-xl-4"><a class="card h-100 shadow-sm text-decoration-none" href="<?= url('student/clubs.php?id=' . $c['id']) ?>"><div class="club-cover"></div><div class="card-body d-flex gap-3">
   <?php if ($c['logo']): ?><img class="club-logo" src="<?= url($c['logo']) ?>" alt=""><?php else: ?><span class="club-logo"><?= e(mb_substr($c['name'], 0, 1)) ?></span><?php endif; ?>
   <div><h6 class="mb-1"><?= e($c['name']) ?></h6><div class="small text-muted"><?= e(mb_strimwidth($c['description'] ?? '', 0, 90, '…')) ?></div><div class="small mt-1"><?= (int) $c['post_count'] ?> posts</div></div></div></a></div>
<?php endforeach; if (!$clubs): ?><div class="empty"><i class="bi bi-people"></i>No clubs found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
$st = get('status', 'pending'); $valid = ['pending', 'published', 'rejected', 'draft', 'archived'];
if (!in_array($st, $valid, true)) $st = 'pending';
if ($st === 'draft') $st = 'pending';   // admins never see club drafts
$posts = rows('SELECT p.*, c.name club_name FROM posts p JOIN clubs c ON c.id=p.club_id WHERE p.status=? ORDER BY p.updated_at DESC LIMIT 200', [$st]);
$portal = 'admin'; $title = 'Post Approvals';
require __DIR__ . '/../includes/header.php'; ?>
<ul class="nav nav-pills mb-3"><?php foreach (['pending' => 'Pending', 'published' => 'Published', 'rejected' => 'Rejected', 'archived' => 'Archived'] as $k => $l): ?><li class="nav-item"><a class="nav-link <?= $st === $k ? 'active' : '' ?>" href="?status=<?= $k ?>"><?= $l ?></a></li><?php endforeach; ?></ul>
<?php if (!$posts): ?><div class="empty"><i class="bi bi-inbox"></i>Nothing here.</div><?php endif; ?>
<div class="list-group"><?php foreach ($posts as $p): ?><a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?= url('admin/post-review.php?id=' . $p['id']) ?>"><div><strong><?= e($p['title']) ?></strong><div class="small text-muted"><?= e($p['club_name']) ?> · updated <?= dt($p['updated_at']) ?> · v<?= (int) $p['version'] ?></div></div><?= status_badge($p['status']) ?></a><?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php';

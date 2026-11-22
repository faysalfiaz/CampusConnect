<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$cid = current_club()['id'];
$pa = rows("SELECT a.*, p.title, 'Post' kind FROM post_approvals a JOIN posts p ON p.id=a.post_id WHERE p.club_id=? ORDER BY a.created_at DESC LIMIT 60", [$cid]);
$ea = rows("SELECT a.*, e.title, 'Event' kind FROM event_approvals a JOIN events e ON e.id=a.event_id WHERE e.club_id=? ORDER BY a.created_at DESC LIMIT 60", [$cid]);
$all = array_merge($pa, $ea); usort($all, fn($x, $y) => strcmp($y['created_at'], $x['created_at']));
$portal = 'club'; $title = 'Approvals & Admin Feedback';
require __DIR__ . '/../includes/header.php'; ?>
<?php if (!$all): ?><div class="empty"><i class="bi bi-clipboard-check"></i>No approval history yet.</div><?php endif; ?>
<div class="list-group"><?php foreach ($all as $a): ?><div class="list-group-item"><span class="badge bg-secondary"><?= $a['kind'] ?></span> <strong><?= e($a['title']) ?></strong> — <?= status_badge($a['action'] === 'approved' ? 'approved' : ($a['action'] === 'rejected' ? 'rejected' : 'pending')) ?> <?= e($a['action']) ?>
<div class="small text-muted"><?= dt($a['created_at']) ?></div><?php if (!empty($a['reason']) && in_array($a['action'], ['rejected', 'cancelled'], true)): ?><div class="small mt-1"><strong>Reason:</strong> <?= e($a['reason']) ?></div><?php endif; ?></div><?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php';

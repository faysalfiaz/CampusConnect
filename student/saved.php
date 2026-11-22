<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id'];
$posts = feed_posts(' AND p.id IN (SELECT post_id FROM post_saves WHERE user_id=?)', [$uid], $uid, 100);
$portal = 'student'; $title = 'Saved Posts';
require __DIR__ . '/../includes/header.php';
?>
<div class="row"><div class="col-lg-8 col-xl-7">
<?php foreach ($posts as $p) include __DIR__ . '/../includes/post_card.php'; ?>
<?php if (!$posts): ?><div class="empty"><i class="bi bi-bookmark"></i>You have not saved any posts.</div><?php endif; ?>
</div></div>
<?php include __DIR__ . '/../includes/comment_forms.php'; require __DIR__ . '/../includes/footer.php';

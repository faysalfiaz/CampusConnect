<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
if (is_post()) {
    csrf_check();
    if (post('action') === 'create') {
        $t = post('title'); $b = post('body'); $em = isset($_POST['is_emergency']) ? 1 : 0;
        if ($t === '' || $b === '') flash('error', 'Title and message are required.');
        else { q('INSERT INTO notices (title, body, is_emergency, created_by) VALUES (?,?,?,?)', [$t, $b, $em, user()['id']]); notify_all_students(($em ? 'EMERGENCY: ' : 'New notice: ') . $t, $b, 'student/notices.php'); log_activity('notice_published', $t); flash('success', 'Notice published and students notified.'); }
    } elseif (post('action') === 'delete') { q('DELETE FROM notices WHERE id=?', [(int) post('id')]); flash('success', 'Notice deleted.'); }
    redirect('admin/notices.php');
}
$notices = rows('SELECT * FROM notices ORDER BY created_at DESC LIMIT 100');
$portal = 'admin'; $title = 'Notices & Announcements';
require __DIR__ . '/../includes/header.php'; ?>
<div class="card shadow-sm mb-4"><div class="card-body"><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<div class="col-12"><input name="title" class="form-control" placeholder="Title" required></div><div class="col-12"><textarea name="body" class="form-control" rows="3" placeholder="Message" required></textarea></div>
<div class="col-12 d-flex gap-3 align-items-center"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_emergency" id="em"><label for="em" class="form-check-label text-danger">Emergency announcement</label></div><button class="btn btn-accent">Publish</button></div></form></div></div>
<?php foreach ($notices as $n): ?><div class="card mb-2 <?= $n['is_emergency'] ? 'border-danger' : '' ?>"><div class="card-body d-flex justify-content-between"><div><strong><?= $n['is_emergency'] ? '🚨 ' : '' ?><?= e($n['title']) ?></strong><div class="small"><?= e($n['body']) ?></div><div class="small text-muted"><?= dt($n['created_at']) ?></div></div>
<form method="post" data-confirm="Delete notice?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $n['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></div></div><?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php';

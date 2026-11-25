<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
// ---- Admin posts (publish / edit / delete) under "Campus Administration" ----
$sysEmail = 'campus.admin@system.local';
$adminClubId = (int) val('SELECT id FROM clubs WHERE official_email=?', [$sysEmail]);
if (!$adminClubId) {   // created automatically the first time
    q("INSERT INTO users (role,email,password_hash,email_verified_at) VALUES ('club',?,?,NOW())", [$sysEmail, '!']);
    $sysUserId = insert_id();
    q('INSERT INTO clubs (user_id,name,category,description,official_email) VALUES (?,?,?,?,?)',
        [$sysUserId, 'Campus Administration', 'Official', 'Official announcements from the university administration.', $sysEmail]);
    $adminClubId = insert_id();
}
$postErr = [];
$act = is_post() ? post('action') : '';
if (in_array($act, ['admin_post', 'admin_post_update', 'admin_post_delete'], true)) {
    csrf_check();
    if ($act === 'admin_post_delete') {
        q('DELETE FROM posts WHERE id=? AND club_id=?', [(int) post('id'), $adminClubId]);
        flash('success', 'Post deleted.');
        redirect('admin/dashboard.php');
    }
    $pf = ['title' => post('title'), 'description' => post('description'), 'category' => post('category'),
           'event_date' => post('event_date') ?: null, 'event_time' => post('event_time') ?: null,
           'event_venue' => post('event_venue'), 'extra_info' => post('extra_info')];
    if ($pf['title'] === '' || $pf['description'] === '') $postErr[] = 'Title and description are required.';
    $pImg = $pAtt = null;
    try { $pImg = upload_file('image', 'posts'); $pAtt = upload_file('attachment', 'posts', false); }
    catch (RuntimeException $ex) { $postErr[] = $ex->getMessage(); }
    if (!$postErr && $act === 'admin_post') {
        q("INSERT INTO posts (club_id,title,description,category,image,attachment,event_date,event_time,event_venue,extra_info,status,published_at)
           VALUES (?,?,?,?,?,?,?,?,?,?,'published',NOW())",
            [$adminClubId, $pf['title'], $pf['description'], $pf['category'], $pImg, $pAtt, $pf['event_date'], $pf['event_time'], $pf['event_venue'], $pf['extra_info']]);
        foreach (rows("SELECT id FROM users WHERE role='student' AND status='active'", []) as $stuRow)
            notify((int) $stuRow['id'], 'New announcement', mb_substr($pf['title'], 0, 200), 'student/feed.php');
        log_activity('admin_post', $pf['title']);
        flash('success', 'Published. Students can see it in their feed now.');
        redirect('admin/dashboard.php');
    }
    if (!$postErr && $act === 'admin_post_update') {
        q('UPDATE posts SET title=?, description=?, category=?, image=COALESCE(?,image), attachment=COALESCE(?,attachment),
           event_date=?, event_time=?, event_venue=?, extra_info=? WHERE id=? AND club_id=?',
            [$pf['title'], $pf['description'], $pf['category'], $pImg, $pAtt, $pf['event_date'], $pf['event_time'], $pf['event_venue'], $pf['extra_info'], (int) post('id'), $adminClubId]);
        log_activity('admin_post_edit', $pf['title']);
        flash('success', 'Post updated. Students see the new version.');
        redirect('admin/dashboard.php');
    }
}
$editPost = null;   // the post being edited (opened with ?edit=ID)
if (get('edit')) $editPost = row('SELECT * FROM posts WHERE id=? AND club_id=?', [(int) get('edit'), $adminClubId]);
if ($act === 'admin_post_update' && $postErr) $editPost = row('SELECT * FROM posts WHERE id=? AND club_id=?', [(int) post('id'), $adminClubId]);
$myAdminPosts = rows('SELECT p.id,p.title,p.published_at,(SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id) likes
                      FROM posts p WHERE p.club_id=? ORDER BY p.id DESC LIMIT 20', [$adminClubId]);
$pv = fn($k) => e($_POST[$k] ?? ($editPost[$k] ?? ''));
$pvTime = e(substr($_POST['event_time'] ?? ($editPost['event_time'] ?? ''), 0, 5));
$n = fn($sql) => (int) val($sql);
$stats = [
 ['Students', $n("SELECT COUNT(*) FROM users WHERE role='student'"), 'mortarboard'], ['Clubs', $n('SELECT COUNT(*) FROM clubs'), 'building'],
 ['Total posts', $n('SELECT COUNT(*) FROM posts'), 'file-earmark-text'], ['Pending posts', $n("SELECT COUNT(*) FROM posts WHERE status='pending'"), 'hourglass-split'],
 ['Published posts', $n("SELECT COUNT(*) FROM posts WHERE status='published'"), 'check-circle'], ['Rejected posts', $n("SELECT COUNT(*) FROM posts WHERE status='rejected'"), 'x-circle'],
 ['Total events', $n('SELECT COUNT(*) FROM events'), 'calendar-event'], ['Pending events', $n("SELECT COUNT(*) FROM events WHERE status='pending'"), 'calendar-check'],
 ['Registrations', $n("SELECT COUNT(*) FROM event_registrations WHERE status='registered'"), 'journal-check'], ['Open reports', $n("SELECT COUNT(*) FROM post_reports WHERE status='open'"), 'flag'],
];
$recent = rows('SELECT l.*, u.email FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 10');
$byStatus = rows('SELECT status, COUNT(*) c FROM posts GROUP BY status');
$noHead = true; $portal = 'admin'; $title = 'Admin Dashboard';
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>
new Chart(document.getElementById("c1"),{type:"doughnut",data:{labels:' . json_encode(array_column($byStatus, 'status')) . ',datasets:[{data:' . json_encode(array_map('intval', array_column($byStatus, 'c'))) . ',backgroundColor:["#94a3b8","#f59e0b","#22c55e","#ef4444","#16a34a","#475569"]}]}});</script>';
require __DIR__ . '/../includes/header.php'; ?><section class="dash-hero"><div class="dh-text"><span class="dh-chip"><i class="bi bi-stars"></i> Admin portal</span><h2>Campus overview</h2><p>Review what is waiting and keep everything running smoothly.</p><div class="dh-actions"><a class="btn btn-light" href="<?= url('admin/posts.php') ?>"><i class="bi bi-check2-square"></i> Review posts</a><a class="btn btn-outline-light" href="<?= url('admin/events.php') ?>"><i class="bi bi-calendar-check"></i> Review events</a><a class="btn btn-outline-light" href="<?= url('admin/analytics.php') ?>"><i class="bi bi-graph-up"></i> Analytics</a></div></div><div class="dh-date"><b><?= date('d') ?></b><span><?= date('M Y') ?></span><small><?= date('l') ?></small></div></section><?php  ?>
<div class="row g-3 mb-4"><?php foreach ($stats as [$l, $v, $i]): ?><div class="col-6 col-lg-3 col-xl-2"><div class="card stat-card shadow-sm"><div class="card-body"><i class="bi bi-<?= $i ?> text-accent"></i> <span class="text-muted small"><?= $l ?></span><div class="num"><?= $v ?></div></div></div></div><?php endforeach; ?></div>
<div class="card mb-4" id="adminPostCard">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-megaphone"></i> Post to students</span>
    <a class="btn btn-sm btn-accent" data-bs-toggle="collapse" href="#adminPost" role="button"><?= $editPost ? 'Editing' : 'New post' ?></a>
  </div>
  <div class="collapse <?= ($postErr || $editPost) ? 'show' : '' ?>" id="adminPost"><div class="card-body">
    <?php foreach ($postErr as $x): ?><div class="alert alert-danger py-2"><?= e($x) ?></div><?php endforeach; ?>
    <?php if ($editPost): ?><div class="alert alert-info py-2"><i class="bi bi-pencil"></i> You are editing: <strong><?= e($editPost['title']) ?></strong></div>
    <?php else: ?><p class="text-muted small">Goes live immediately under <strong>Campus Administration</strong> and appears in every student's feed. Use it for events, notices and announcements.</p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editPost ? 'admin_post_update' : 'admin_post' ?>">
      <?php if ($editPost): ?><input type="hidden" name="id" value="<?= (int) $editPost['id'] ?>"><?php endif; ?>
      <div class="col-md-8"><label class="form-label">Title *</label><input name="title" class="form-control" maxlength="200" value="<?= $pv('title') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= $pv('category') ?>" placeholder="Event, Notice, Contest…"></div>
      <div class="col-12"><label class="form-label">Description *</label><textarea name="description" rows="4" class="form-control" required><?= $pv('description') ?></textarea></div>
      <div class="col-md-4"><label class="form-label">Event date</label><input type="date" name="event_date" class="form-control" value="<?= $pv('event_date') ?>"></div>
      <div class="col-md-4"><label class="form-label">Event time</label><input type="time" name="event_time" class="form-control" value="<?= $pvTime ?>"></div>
      <div class="col-md-4"><label class="form-label">Venue</label><input name="event_venue" class="form-control" value="<?= $pv('event_venue') ?>"></div>
      <div class="col-12"><label class="form-label">Additional information</label><textarea name="extra_info" rows="2" class="form-control"><?= $pv('extra_info') ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Poster / image</label><input type="file" name="image" accept="image/*" class="form-control"><?php if ($editPost && $editPost['image']): ?><div class="form-text">A poster is attached. Choose a file only to replace it.</div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Attachment (image or PDF)</label><input type="file" name="attachment" accept="image/*,.pdf" class="form-control"><?php if ($editPost && $editPost['attachment']): ?><div class="form-text">A file is attached. Choose a file only to replace it.</div><?php endif; ?></div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-accent"><i class="bi bi-send"></i> <?= $editPost ? 'Save changes' : 'Publish to students' ?></button>
        <?php if ($editPost): ?><a class="btn btn-outline-secondary" href="<?= url('admin/dashboard.php') ?>">Cancel edit</a><?php endif; ?>
      </div>
    </form>
    <?php if ($myAdminPosts): ?><hr><div class="small fw-semibold mb-2">Your admin posts</div>
    <div class="list-group list-group-flush">
    <?php foreach ($myAdminPosts as $m): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center gap-2 px-0">
        <div><?= e($m['title']) ?><br><small class="text-muted"><?= e(dt($m['published_at'], 'd M Y')) ?> · <?= (int) $m['likes'] ?> likes</small></div>
        <div class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-secondary" href="?edit=<?= (int) $m['id'] ?>#adminPostCard"><i class="bi bi-pencil"></i> Edit</a>
          <form method="post" data-confirm="Delete this post?"><?= csrf_field() ?><input type="hidden" name="action" value="admin_post_delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form>
        </div>
      </div>
    <?php endforeach; ?></div><?php endif; ?>
  </div></div>
</div>
<div class="row g-3"><div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Posts by status</div><div class="card-body"><canvas id="c1"></canvas></div></div></div>
<div class="col-lg-8"><div class="card shadow-sm"><div class="card-header">Recent activity</div><ul class="list-group list-group-flush small">
<?php foreach ($recent as $r): ?><li class="list-group-item"><strong><?= e(str_replace('_', ' ', $r['action'])) ?></strong> <span class="text-muted"><?= e($r['email'] ?? '') ?> <?= e($r['role'] ? '(' . $r['role'] . ')' : '') ?> <?= e($r['details']) ?> · <?= dt($r['created_at']) ?></span></li><?php endforeach; ?></ul></div></div></div>
<?php require __DIR__ . '/../includes/footer.php';
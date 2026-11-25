<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $cid = $c['id']; $uid = user()['id'];
$id = (int) get('id'); $post = null; $err = [];
if ($id) {
    $post = row('SELECT * FROM posts WHERE id=? AND club_id=?', [$id, $cid]);   // a club can only open its own posts
    if (!$post) { http_response_code(404); exit('Post not found.'); }
    if (in_array($post['status'], ['archived'], true)) { flash('error', 'Archived posts cannot be edited.'); redirect('club/posts.php'); }
}
$posters = rows('SELECT id, full_name, position FROM club_members WHERE club_id=? AND can_post=1 ORDER BY full_name', [$cid]);
if (is_post()) {
    csrf_check();
    $mode = post('mode'); // draft | submit
    $f = ['title' => post('title'), 'description' => post('description'), 'category' => post('category'), 'event_date' => post('event_date') ?: null, 'event_time' => post('event_time') ?: null, 'event_venue' => post('event_venue'), 'extra_info' => post('extra_info')];
    $by = (int) post('member_id');
    if ($f['title'] === '' || $f['description'] === '') $err[] = 'Title and description are required.';
    if (!val('SELECT id FROM club_members WHERE id=? AND club_id=? AND can_post=1', [$by, $cid])) $err[] = 'Choose an authorised executive as the author (set posting permission on the Executives page).';
    try { $img = upload_file('image', 'posts'); $att = upload_file('attachment', 'posts', false); } catch (RuntimeException $ex) { $err[] = $ex->getMessage(); }
    if (!$err) {
        $image = !empty($img) ? $img : ($post['image'] ?? null);
        $attach = !empty($att) ? $att : ($post['attachment'] ?? null);
        if (!$post) {
            $status = $mode === 'submit' ? 'pending' : 'draft';
            q('INSERT INTO posts (club_id, created_by_member_id, title, description, category, image, attachment, event_date, event_time, event_venue, extra_info, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$cid, $by, $f['title'], $f['description'], $f['category'], $image, $attach, $f['event_date'], $f['event_time'], $f['event_venue'], $f['extra_info'], $status]);
            $pid = insert_id();
            if ($status === 'pending') { q("INSERT INTO post_approvals (post_id, action, actor_user_id) VALUES (?, 'submitted', ?)", [$pid, $uid]); notify_admins('New post submitted', $c['name'] . ': ' . $f['title'], 'admin/post-review.php?id=' . $pid); }
            log_activity('post_' . $status, $f['title']);
            flash('success', $status === 'pending' ? 'Submitted for admin approval.' : 'Draft saved.');
        } else {
            $nt = fn($t) => $t ? substr((string) $t, 0, 5) : '';
            $changed = $post['title'] !== $f['title'] || $post['description'] !== $f['description'] || (string) $post['category'] !== $f['category']
                || (string) $post['event_date'] !== (string) $f['event_date'] || $nt($post['event_time']) !== $nt($f['event_time'])
                || (string) $post['event_venue'] !== $f['event_venue'] || (string) $post['extra_info'] !== $f['extra_info']
                || $image !== $post['image'] || $attach !== $post['attachment'];
            $wasLive = in_array($post['status'], ['published', 'approved'], true);
            $status = $post['status'];
            $action = null;
            if (in_array($status, ['draft', 'rejected'], true)) {
                if ($mode === 'submit') {
                    $status = 'pending';
                    $action = $post['status'] === 'rejected' ? 'resubmitted' : 'submitted';
                }
            } elseif ($wasLive && $changed) {
                $status = 'approved';
                $action = 'updated';
            }
            q('UPDATE posts SET created_by_member_id=?, title=?, description=?, category=?, image=?, attachment=?, event_date=?, event_time=?, event_venue=?, extra_info=?, status=?, version=version+?, rejection_reason=IF(?= "pending", NULL, rejection_reason) WHERE id=? AND club_id=?',
                [$by, $f['title'], $f['description'], $f['category'], $image, $attach, $f['event_date'], $f['event_time'], $f['event_venue'], $f['extra_info'], $status, $changed ? 1 : 0, $status, $id, $cid]);
            if ($action === 'updated') {
                notify_admins('Post updated', $c['name'] . ' updated a live post: ' . $f['title'], 'admin/post-review.php?id=' . $id);
            } elseif ($action) {
                q('INSERT INTO post_approvals (post_id, action, actor_user_id) VALUES (?,?,?)', [$id, $action, $uid]);
                notify_admins('Post ' . $action, $c['name'] . ': ' . $f['title'], 'admin/post-review.php?id=' . $id);
            }
            log_activity('post_updated', $f['title']);
            flash('success', $wasLive && $changed ? 'Post updated! Changes are now live for students and admins have been notified.' : ($action ? 'Sent for admin approval.' : 'Saved.'));
        }
        redirect('club/posts.php');
    }
}
$v = fn($k) => e($_POST[$k] ?? ($post[$k] ?? ''));
$portal = 'club'; $title = $post ? 'Edit Post' : 'Create Post';
require __DIR__ . '/../includes/header.php';
if ($post && $post['status'] === 'rejected' && $post['rejection_reason']): ?><div class="alert alert-danger"><strong>Rejected by admin:</strong> <?= e($post['rejection_reason']) ?></div><?php endif;
if ($post && in_array($post['status'], ['published', 'approved'], true)): ?><div class="alert alert-info">This post is live. Any changes you save will remain visible to students immediately and notify the admin.</div><?php endif;
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>';
if (!$posters) echo '<div class="alert alert-warning">No executive has posting permission yet. Add one on the <a href="' . url('club/executives.php') . '">Executives</a> page.</div>'; ?>
<div class="card shadow-sm"><div class="card-body"><form method="post" enctype="multipart/form-data" class="row g-3" id="postForm"><?= csrf_field() ?>
 <div class="col-md-8"><label class="form-label">Title *</label><input name="title" id="pTitle" class="form-control" maxlength="200" value="<?= $v('title') ?>" required></div>
 <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= $v('category') ?>" placeholder="Workshop, Notice, Contest…"></div>
 <div class="col-12"><label class="form-label">Description *</label><textarea name="description" id="pDesc" rows="5" class="form-control" required><?= $v('description') ?></textarea></div>
 <div class="col-md-4"><label class="form-label">Event date</label><input type="date" name="event_date" class="form-control" value="<?= $v('event_date') ?>"></div>
 <div class="col-md-4"><label class="form-label">Event time</label><input type="time" name="event_time" class="form-control" value="<?= e(substr($_POST['event_time'] ?? ($post['event_time'] ?? ''), 0, 5)) ?>"></div>
 <div class="col-md-4"><label class="form-label">Venue</label><input name="event_venue" class="form-control" value="<?= $v('event_venue') ?>"></div>
 <div class="col-12"><label class="form-label">Additional information</label><textarea name="extra_info" rows="2" class="form-control"><?= $v('extra_info') ?></textarea></div>
 <div class="col-md-6"><label class="form-label">Poster / image</label><input type="file" name="image" id="pImg" accept="image/*" class="form-control"><?php if (!empty($post['image'])): ?><img src="<?= url($post['image']) ?>" height="60" class="mt-2 rounded" alt=""><?php endif; ?></div>
 <div class="col-md-6"><label class="form-label">Attachment (image or PDF)</label><input type="file" name="attachment" accept="image/*,.pdf" class="form-control"></div>
 <div class="col-md-6"><label class="form-label">Posted by (authorised executive) *</label><select name="member_id" class="form-select" required><option value="">Select…</option><?php foreach ($posters as $m): ?><option value="<?= $m['id'] ?>" <?= (int) ($_POST['member_id'] ?? ($post['created_by_member_id'] ?? 0)) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?> (<?= e($m['position']) ?>)</option><?php endforeach; ?></select></div>
 <div class="col-12 d-flex flex-wrap gap-2">
   <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#previewModal" id="previewBtn"><i class="bi bi-eye"></i> Preview</button>
   <?php if (!$post || in_array($post['status'], ['draft'], true)): ?><button name="mode" value="draft" class="btn btn-outline-primary">Save as draft</button><?php endif; ?>
   <button name="mode" value="submit" class="btn btn-accent"><?= $post && in_array($post['status'], ['published', 'approved'], true) ? 'Save changes' : 'Submit for approval' ?></button>
 </div></form></div></div>
<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Preview</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body"><div class="small text-muted mb-1"><?= e($c['name']) ?></div><h5 id="vTitle"></h5><p id="vDesc" style="white-space:pre-line"></p><img id="vImg" class="img-fluid rounded" alt=""></div></div></div></div>
<?php $extra_js = '<script>document.getElementById("previewBtn").addEventListener("click",function(){document.getElementById("vTitle").textContent=document.getElementById("pTitle").value;document.getElementById("vDesc").textContent=document.getElementById("pDesc").value;var f=document.getElementById("pImg").files[0],i=document.getElementById("vImg");if(f){i.src=URL.createObjectURL(f);i.style.display="block"}else{i.style.display="none"}});</script>';
require __DIR__ . '/../includes/footer.php';
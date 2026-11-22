<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('club');
$c = current_club(); $err = [];
if (is_post()) {
    csrf_check();
    $desc = post('description'); $cat = post('category');
    try { $logo = upload_file('logo', 'logos'); $cover = upload_file('cover', 'logos'); } catch (RuntimeException $ex) { $err[] = $ex->getMessage(); }
    if (!$err) {
        q('UPDATE clubs SET description=?, category=?, logo=COALESCE(?,logo), cover=COALESCE(?,cover) WHERE id=?', [$desc, $cat ?: null, $logo ?? null, $cover ?? null, $c['id']]);
        log_activity('club_profile_updated'); flash('success', 'Club profile saved.'); redirect('club/profile.php');
    }
}
$portal = 'club'; $title = 'Club Profile';
require __DIR__ . '/../includes/header.php';
foreach ($err as $x) echo '<div class="alert alert-danger py-2">' . e($x) . '</div>'; ?>
<div class="cover mb-3" <?= $c['cover'] ? 'style="background-image:url(' . e(url($c['cover'])) . ')"' : '' ?>></div>
<div class="card shadow-sm"><div class="card-body"><form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
 <div class="col-md-6"><label class="form-label">Club name</label><input class="form-control" value="<?= e($c['name']) ?>" disabled><div class="form-text">Name and official email are managed by the admin.</div></div>
 <div class="col-md-6"><label class="form-label">Official email</label><input class="form-control" value="<?= e($c['official_email']) ?>" disabled></div>
 <div class="col-md-6"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= e($c['category']) ?>" placeholder="e.g. Technology"></div>
 <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="5" class="form-control"><?= e($c['description']) ?></textarea></div>
 <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" accept="image/*" class="form-control"><?php if ($c['logo']): ?><img src="<?= url($c['logo']) ?>" height="50" class="mt-2 rounded" alt=""><?php endif; ?></div>
 <div class="col-md-6"><label class="form-label">Cover image</label><input type="file" name="cover" accept="image/*" class="form-control"></div>
 <div class="col-12"><button class="btn btn-accent">Save profile</button></div></form></div></div>
<?php require __DIR__ . '/../includes/footer.php';

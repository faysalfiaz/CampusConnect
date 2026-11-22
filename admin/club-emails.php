<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('admin');
if (is_post()) {
    csrf_check(); $r = row("SELECT e.*, c.user_id, c.name, c.official_email FROM club_emails e JOIN clubs c ON c.id=e.club_id WHERE e.id=? AND e.status='pending'", [(int) post('id')]);
    if ($r) {
        if (post('action') === 'approve') {
            if (val("SELECT id FROM users WHERE email=? AND role='club' AND id<>?", [$r['email'], $r['user_id']])) flash('error', 'That email is already in use.');
            else {
                q("UPDATE club_emails SET status='replaced' WHERE club_id=? AND status='verified'", [$r['club_id']]);
                q("UPDATE club_emails SET status='verified', decided_at=NOW() WHERE id=?", [$r['id']]);
                q('UPDATE clubs SET official_email=? WHERE id=?', [$r['email'], $r['club_id']]); q('UPDATE users SET email=? WHERE id=?', [$r['email'], $r['user_id']]);
                notify((int) $r['user_id'], 'Official email updated', 'Your new login email is ' . $r['email'], 'club/settings.php'); log_activity('club_email_approved', $r['name'] . ' → ' . $r['email']); flash('success', 'Email change approved.');
            }
        } else { q("UPDATE club_emails SET status='rejected', decided_at=NOW(), note=? WHERE id=?", [post('note') ?: 'Rejected', $r['id']]); notify((int) $r['user_id'], 'Email change rejected', post('note') ?: '', 'club/settings.php'); log_activity('club_email_rejected', $r['name']); flash('success', 'Request rejected.'); }
    }
    redirect('admin/club-emails.php');
}
$pending = rows("SELECT e.*, c.name, c.official_email current_email FROM club_emails e JOIN clubs c ON c.id=e.club_id WHERE e.status='pending' ORDER BY e.requested_at");
$hist = rows("SELECT e.*, c.name FROM club_emails e JOIN clubs c ON c.id=e.club_id WHERE e.status<>'pending' ORDER BY COALESCE(e.decided_at,e.requested_at) DESC LIMIT 100");
$portal = 'admin'; $title = 'Club Official Emails';
require __DIR__ . '/../includes/header.php'; ?>
<div class="alert alert-info small">Admin-registered emails are recorded as verified. This system does not claim OAuth/mailbox verification; if you need it, configure an approved provider (see documentation/installation.md).</div>
<h6>Pending change requests</h6>
<?php if (!$pending) echo '<div class="empty py-3">No pending requests.</div>'; foreach ($pending as $p): ?>
<div class="card mb-2"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><div><strong><?= e($p['name']) ?></strong><div class="small"><?= e($p['current_email']) ?> → <strong><?= e($p['email']) ?></strong><br><?= e($p['note']) ?></div></div>
<form method="post" class="d-flex gap-1 align-items-start"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input name="note" class="form-control form-control-sm" placeholder="Note (for rejection)"><button name="action" value="approve" class="btn btn-sm btn-success">Approve</button><button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button></form></div></div><?php endforeach; ?>
<h6 class="mt-4">History</h6><ul class="list-group small"><?php foreach ($hist as $h): ?><li class="list-group-item"><?= e($h['name']) ?> — <?= e($h['email']) ?> — <strong><?= e($h['status']) ?></strong> <span class="text-muted"><?= dt($h['decided_at'] ?: $h['requested_at']) ?> <?= e($h['note']) ?></span></li><?php endforeach; ?></ul>
<?php require __DIR__ . '/../includes/footer.php';

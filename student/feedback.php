<?php
require_once __DIR__ . '/../includes/auth.php'; require_role('student');
$uid = user()['id']; $portal = 'student'; $title = 'My feedback';
$list = rows('SELECT e.id,e.title,e.event_date,c.name club_name,f.rating,f.comment FROM attendance a
  JOIN event_tickets t ON t.id=a.ticket_id JOIN event_registrations r ON r.id=t.registration_id AND r.user_id=?
  JOIN events e ON e.id=a.event_id JOIN clubs c ON c.id=e.club_id LEFT JOIN event_feedback f ON f.event_id=e.id AND f.user_id=?
  ORDER BY e.event_date DESC', [$uid, $uid]);
require __DIR__ . '/../includes/header.php'; ?>
<p class="text-muted">Rate the events you attended. Clubs see your rating and comment without your name.</p>
<?php foreach ($list as $x): ?>
<div class="card mb-3"><div class="card-body">
  <div class="fw-semibold"><?= e($x['title']) ?></div><div class="small text-muted mb-2"><?= e($x['club_name']) ?> · <?= e(dt($x['event_date'], 'd M Y')) ?></div>
  <form method="post" action="<?= url('api/feedback.php') ?>"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $x['id'] ?>">
    <div class="stars" role="radiogroup" aria-label="Rating">
      <?php for ($s = 5; $s >= 1; $s--): ?><input type="radio" name="rating" id="r<?= (int) $x['id'] ?>-<?= $s ?>" value="<?= $s ?>" <?= (int) $x['rating'] === $s ? 'checked' : '' ?> required><label for="r<?= (int) $x['id'] ?>-<?= $s ?>" title="<?= $s ?> stars">★</label><?php endfor; ?>
    </div>
    <textarea name="comment" class="form-control my-2" rows="2" maxlength="500" placeholder="What went well? What could be better?"><?= e($x['comment']) ?></textarea>
    <button class="btn btn-sm btn-accent"><?= $x['rating'] ? 'Update feedback' : 'Send feedback' ?></button>
  </form></div></div>
<?php endforeach; ?>
<?php if (!$list): ?><div class="empty"><i class="bi bi-star"></i>Attend an event and its feedback form will appear here.</div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php';

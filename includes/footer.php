<p class="site-foot"><?= APP_NAME ?> &middot; Clubs, events and campus life</p>
</main>
<?php if (isset($menus[$portal])): ?></div><?php endif; ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts">
<?php foreach (flash() as [$t, $m]): ?>
  <div class="toast align-items-center text-bg-<?= $t === 'error' ? 'danger' : ($t === 'success' ? 'success' : 'info') ?> border-0 show" role="alert" data-bs-delay="5000">
    <div class="d-flex"><div class="toast-body"><?= e($m) ?></div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
<?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.CCP = { base: "<?= BASE_URL ?>", csrf: "<?= csrf_token() ?>" };</script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($extra_js)) echo $extra_js; ?>
</body></html>

(function () {
  // dark / light mode
  var btn = document.getElementById('themeToggle');
  if (btn) btn.addEventListener('click', function () {
    var cur = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', cur); localStorage.setItem('ccp-theme', cur);
  });
  // auto-hide toasts
  document.querySelectorAll('.toast').forEach(function (t) { setTimeout(function () { t.remove(); }, 5000); });
  // confirm dialogs: <form data-confirm="Are you sure?">
  document.addEventListener('submit', function (e) {
    var m = e.target.getAttribute('data-confirm');
    if (m && !confirm(m)) e.preventDefault();
  });
  // loading state on submit buttons
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var b = e.submitter; if (b && !b.dataset.noLoading) { setTimeout(function () { b.disabled = true; }, 0); }
  });
  // AJAX toggles: like / save
  function ajax(url, data) {
    var fd = new FormData(); for (var k in data) fd.append(k, data[k]); fd.append('csrf', window.CCP.csrf);
    return fetch(window.CCP.base + '/' + url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); });
  }
  document.addEventListener('click', function (e) {
    var like = e.target.closest('.like-btn');
    if (like) { ajax('api/likes.php', { post_id: like.dataset.id }).then(function (j) {
      if (j.ok) { like.classList.toggle('liked', j.liked); like.querySelector('i').className = 'bi bi-heart' + (j.liked ? '-fill' : ''); like.querySelector('.count').textContent = j.count; } }); }
    var save = e.target.closest('.save-btn');
    if (save) { ajax('api/saves.php', { post_id: save.dataset.id }).then(function (j) {
      if (j.ok) { save.classList.toggle('saved', j.saved); save.querySelector('i').className = 'bi bi-bookmark' + (j.saved ? '-fill' : ''); } }); }
    var edit = e.target.closest('.edit-comment');
    if (edit) { var nb = prompt('Edit your comment:', edit.dataset.body); if (nb !== null && nb.trim()) {
      var f = document.getElementById('editCommentForm'); f.querySelector('[name=comment_id]').value = edit.dataset.id; f.querySelector('[name=body]').value = nb; f.submit(); } }
    var rep = e.target.closest('.report-comment');
    if (rep) { var r = prompt('Why is this comment inappropriate?'); if (r && r.trim()) {
      var f2 = document.getElementById('reportCommentForm'); f2.querySelector('[name=comment_id]').value = rep.dataset.id; f2.querySelector('[name=reason]').value = r; f2.submit(); } }
  });
})();

(function () { // v2 interactions: live filter, validation, password meter, char counter
  // live filter for long tables and lists
  document.querySelectorAll('table.table, .list-group').forEach(function (box) {
    var items = box.matches('table') ? box.querySelectorAll('tbody tr') : box.querySelectorAll(':scope > .list-group-item');
    if (items.length < 7) return;
    var input = document.createElement('input');
    input.type = 'search'; input.placeholder = 'Filter this list…'; input.className = 'form-control form-control-sm live-filter';
    input.setAttribute('aria-label', 'Filter this list');
    var host = box.closest('.table-responsive') || box; host.parentNode.insertBefore(input, host);
    input.addEventListener('input', function () {
      var t = input.value.toLowerCase();
      items.forEach(function (el) { el.style.display = el.textContent.toLowerCase().indexOf(t) > -1 ? '' : 'none'; });
    });
  });
  // client-side validation feedback (server still validates everything)
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!f.checkValidity()) { e.preventDefault(); e.stopPropagation(); f.classList.add('was-validated'); var bad = f.querySelector(':invalid'); if (bad) bad.focus(); }
    }, true);
  });
  // character counters
  document.querySelectorAll('textarea[maxlength]').forEach(function (t) {
    var c = document.createElement('div'); c.className = 'char-count'; t.after(c);
    var u = function () { c.textContent = t.value.length + ' / ' + t.maxLength; }; t.addEventListener('input', u); u();
  });
})();

document.querySelectorAll('input[type=password]').forEach(function (inp) { // eye icon on every password field
  if (inp.parentNode.classList.contains('pw-wrap')) return;
  var w = document.createElement('div'); w.className = 'pw-wrap'; inp.parentNode.insertBefore(w, inp); w.appendChild(inp);
  var b = document.createElement('button'); b.type = 'button'; b.className = 'pw-toggle'; b.setAttribute('aria-label', 'Show or hide password');
  b.innerHTML = '<i class="bi bi-eye"></i>'; w.appendChild(b);
  b.addEventListener('click', function () {
    var show = inp.type === 'password'; inp.type = show ? 'text' : 'password'; b.firstChild.className = 'bi bi-eye' + (show ? '-slash' : '');
  });
});

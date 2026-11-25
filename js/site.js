/* Interaksi ringan halaman publik. Semua data utama dirender di server; skrip ini hanya tambahan. */
(function () {
  'use strict';
  var root = document.documentElement, $ = function (s) { return document.querySelector(s); };
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  if (store('mis-size') === '1') root.classList.add('large');
  var th = store('mis-theme'); if (th === 'dark' || th === 'light') root.setAttribute('data-theme', th);
  var bs = $('#btn-size'); if (bs) { bs.setAttribute('aria-pressed', root.classList.contains('large')); bs.addEventListener('click', function () { var on = root.classList.toggle('large'); bs.setAttribute('aria-pressed', on); store('mis-size', on ? '1' : '0'); }); }
  var bt = $('#btn-theme'); if (bt) bt.addEventListener('click', function () {
    var cur = root.getAttribute('data-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var nx = cur === 'dark' ? 'light' : 'dark'; root.setAttribute('data-theme', nx); store('mis-theme', nx);
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return; var t = b.getAttribute('data-copy'), l = b.textContent;
    function done(m) { b.textContent = m; setTimeout(function () { b.textContent = l; }, 1600); }
    try { navigator.clipboard.writeText(t).then(function () { done('Tersalin'); }, function () { done(t); }); } catch (err) { done(t); }
  });

  // Peta Google dimuat hanya bila diminta (hemat data dan tanpa pelacakan pihak ketiga sebelum ada persetujuan).
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.mapbox button'); if (!b) return;
    var box = b.parentNode, f = document.createElement('iframe');
    f.src = box.getAttribute('data-map'); f.title = 'Peta lokasi masjid'; f.loading = 'lazy'; f.referrerPolicy = 'no-referrer-when-downgrade';
    box.innerHTML = ''; box.appendChild(f);
  });

/* Lembar menu (ponsel): memakai <dialog> bawaan peramban, jadi fokus, tombol Esc, dan pembaca layar tertangani. */
(function () {
  'use strict';
  var sheet = document.getElementById('sheet'), bm = document.getElementById('btn-menu');
  if (!sheet || !bm || !sheet.showModal) return;
  bm.setAttribute('aria-expanded', 'false');
  bm.addEventListener('click', function () { sheet.showModal(); bm.setAttribute('aria-expanded', 'true'); });
  sheet.addEventListener('close', function () { bm.setAttribute('aria-expanded', 'false'); bm.focus(); });
  sheet.addEventListener('click', function (e) { if (e.target === sheet || e.target.closest('[data-close]')) sheet.close(); });
})();

  document.addEventListener('click', function (e) { if (e.target.closest('[data-print]')) window.print(); });

  // Pemutar YouTube ringan: iframe (youtube-nocookie) baru dimuat setelah tombol ditekan.
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.yt-play'); if (!b) return;
    var box = b.closest('.yt'), id = box.getAttribute('data-yt'); if (!/^[\w-]{11}$/.test(id)) return;
    var f = document.createElement('iframe');
    f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
    f.title = 'Video YouTube'; f.allow = 'autoplay; encrypted-media; picture-in-picture'; f.allowFullscreen = true;
    box.innerHTML = ''; box.appendChild(f);
  });
  // Tombol "Lainnya" memakai menu bagikan bawaan ponsel bila tersedia.
  document.querySelectorAll('[data-share]').forEach(function (b) {
    if (!navigator.share) return; b.hidden = false;
    b.addEventListener('click', function () { navigator.share({ title: b.getAttribute('data-title'), url: b.getAttribute('data-url') }).catch(function () {}); });
  });

  // Tombol WhatsApp melayang baru muncul setelah hero terlewati, agar tidak menutupi isi layar pertama.
  var fab = document.querySelector('.fab');
  if (fab) {
    var fabOn = function () { fab.classList.toggle('on', window.scrollY > 480); };
    fab.classList.add('wait'); fabOn(); window.addEventListener('scroll', fabOn, { passive: true });
  }

  var pb = $('#poster-btn'), pf = $('#poster-fig');
  if (pb && pf) pb.addEventListener('click', function () { var on = pf.classList.toggle('open'); pb.setAttribute('aria-expanded', on); pb.textContent = on ? 'Perkecil poster' : 'Perbesar poster'; });

  // Hitung mundur waktu shalat berikutnya, berdasar jam WITA (bukan zona perangkat pengunjung).
  var box = $('#prayer'); if (!box) return;
  var T = JSON.parse(box.getAttribute('data-times')), main = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];
  var NAME = { fajr: 'Subuh', dhuhr: 'Dzuhur', asr: 'Ashar', maghrib: 'Maghrib', isha: 'Isya' };
  function hhmm(m) { m = ((m % 1440) + 1440) % 1440; return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0'); }
  function tick() {
    var w = new Date(Date.now() + 8 * 3600000), cur = w.getUTCHours() * 60 + w.getUTCMinutes();
    var next = main.filter(function (k) { return T[k] > cur; })[0], nm, at;
    if (next) { nm = NAME[next]; at = T[next]; } else { nm = 'Subuh besok'; at = T.fajr + 1440; }
    var diff = at - cur;
    $('#p-next-label').textContent = nm + ' pukul ' + hhmm(at) + ' WITA';
    $('#p-next').textContent = (diff >= 60 ? Math.floor(diff / 60) + ' jam ' : '') + (diff % 60 || diff < 60 ? (diff % 60) + ' mnt ' : '') + 'lagi';
    var active = null; main.forEach(function (k) { if (T[k] <= cur) active = k; }); if (active === 'fajr' && cur >= T.sunrise) active = null;
    document.querySelectorAll('#p-times .pt').forEach(function (el) { el.classList.toggle('now', el.getAttribute('data-k') === active); });
    var line = $('#p-line'); if (line) line.style.width = Math.max(0, Math.min(100, (cur - T.fajr) * 100 / (T.isha - T.fajr))) + '%';
  }
  tick(); setInterval(tick, 30000);
})();

// Kotak centang yang langsung mengirim formulir (pengganti onchange inline, agar lolos CSP).
document.querySelectorAll('[data-autosubmit]').forEach(function (el) { el.addEventListener('change', function () { el.form && el.form.submit(); }); });

// Menu "Tentang masjid": tutup saat klik di luar atau tekan Escape.
(function () {
  var subs = document.querySelectorAll('.navsub');
  if (!subs.length) return;
  document.addEventListener('click', function (e) { subs.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') subs.forEach(function (d) { if (d.open) { d.open = false; d.querySelector('summary').focus(); } }); });
})();

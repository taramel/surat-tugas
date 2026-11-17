(function () {
  'use strict';

  // Indikator kemajuan tipis di atas halaman dan status "memproses" pada tombol kirim.
  var polaUnduhan = /\/(unduh|ekspor|pdf|pdf_banyak|cadangan_unduh)(\/|$|\?)/;
  var batasWaktu = null;
  var bilahMuat = null;
  var tombolMuat = null;

  function mulaiMuat(tombol) {
    selesaiMuat();
    if (!bilahMuat) {
      bilahMuat = document.createElement('div');
      bilahMuat.className = 'bilah-muat';
      bilahMuat.setAttribute('role', 'progressbar');
      bilahMuat.setAttribute('aria-label', 'Memuat halaman');
      document.body.appendChild(bilahMuat);
    }
    void bilahMuat.offsetWidth;
    bilahMuat.classList.add('jalan');
    if (tombol) {
      tombolMuat = tombol;
      tombol.classList.add('btn-muat');
      tombol.setAttribute('aria-busy', 'true');
    }
    // Pengaman: bila halaman tidak berpindah (misalnya unduhan), pulihkan tampilan.
    batasWaktu = setTimeout(selesaiMuat, 20000);
  }

  function selesaiMuat() {
    clearTimeout(batasWaktu);
    if (bilahMuat) { bilahMuat.classList.remove('jalan'); }
    if (tombolMuat) {
      tombolMuat.classList.remove('btn-muat');
      tombolMuat.removeAttribute('aria-busy');
      tombolMuat = null;
    }
  }

  function tautanBiasa(a, e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return false; }
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(mailto|tel|javascript):/i.test(href)) { return false; }
    if (a.hasAttribute('download') || a.hasAttribute('data-tanpa-muat')) { return false; }
    if (a.target && a.target !== '_self') { return false; }
    if (a.origin !== window.location.origin) { return false; }
    if (a.pathname === window.location.pathname && a.search === window.location.search) { return false; }
    return !polaUnduhan.test(a.pathname + a.search);
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (a && tautanBiasa(a, e)) { mulaiMuat(null); }
  });

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (e.defaultPrevented || f.hasAttribute('data-tanpa-muat')) { return; }
    if (f.target && f.target !== '_self') { return; }
    if (polaUnduhan.test(f.getAttribute('action') || '')) { return; }
    var pengirim = e.submitter || f.querySelector('button[type=submit], button:not([type])');
    mulaiMuat(pengirim && pengirim.classList.contains('btn') ? pengirim : null);
  });

  // Pulihkan tampilan saat kembali lewat riwayat peramban
  window.addEventListener('pageshow', function (e) { if (e.persisted) { selesaiMuat(); } });
})();

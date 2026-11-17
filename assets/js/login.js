(function () {
  'use strict';
  var tombol = document.getElementById('login-lihat');
  var isi = document.getElementById('login-sandi');
  if (!tombol || !isi) { return; }
  tombol.addEventListener('click', function () {
    var tampil = isi.type === 'password';
    isi.type = tampil ? 'text' : 'password';
    tombol.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    var label = tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
    tombol.setAttribute('aria-label', label);
    tombol.title = label;
    var pakai = tombol.querySelector('use');
    if (pakai) { pakai.setAttribute('href', tampil ? '#i-sembunyi' : '#i-lihat'); }
    isi.focus();
  });
})();

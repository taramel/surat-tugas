(function () {
  'use strict';

  var baseUrl = (document.body.getAttribute('data-base') || '/').replace(/\/+$/, '') + '/';

  function siteUrl(path) {
    return baseUrl + (path || '').replace(/^\/+/, '');
  }

  // 1. Menu samping pada layar kecil
  var tombol = document.getElementById('menu-tombol');
  var sisi = document.getElementById('sisi');
  var tutup = document.getElementById('sisi-tutup');
  function aturMenu(buka) {
    sisi.classList.toggle('buka', buka);
    if (tutup) { tutup.classList.toggle('buka', buka); }
    tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
  }
  if (tombol && sisi) {
    tombol.addEventListener('click', function () { aturMenu(!sisi.classList.contains('buka')); });
    if (tutup) { tutup.addEventListener('click', function () { aturMenu(false); }); }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sisi.classList.contains('buka')) { aturMenu(false); tombol.focus(); }
    });
  }

  // 2. Konfirmasi sebelum kirim formulir tertentu (dialog, bukan window.confirm)
  function tanyaKonfirmasi(pesan, bahaya, setuju) {
    var terakhir = document.activeElement;
    var latar = document.createElement('div');
    latar.className = 'dialog-latar';
    var kotak = document.createElement('div');
    kotak.className = 'dialog' + (bahaya ? ' bahaya' : '');
    kotak.setAttribute('role', 'alertdialog');
    kotak.setAttribute('aria-modal', 'true');
    kotak.setAttribute('aria-labelledby', 'dialog-judul');
    kotak.setAttribute('aria-describedby', 'dialog-pesan');
    kotak.innerHTML =
      '<h2 id="dialog-judul"><svg class="ik" aria-hidden="true"><use href="#i-peringatan"></use></svg>Konfirmasi tindakan</h2>' +
      '<p id="dialog-pesan"></p>' +
      '<div class="aksi"><button type="button" class="btn" data-batal>Batal</button>' +
      '<button type="button" class="btn ' + (bahaya ? 'btn-danger' : 'btn-primary') + '" data-lanjut>Ya, lanjutkan</button></div>';
    kotak.querySelector('#dialog-pesan').textContent = pesan;
    latar.appendChild(kotak);
    document.body.appendChild(latar);
    var lanjut = kotak.querySelector('[data-lanjut]');
    var batal = kotak.querySelector('[data-batal]');
    lanjut.focus();
    function tutupDialog() {
      document.removeEventListener('keydown', tombolKey, true);
      latar.remove();
      if (terakhir && terakhir.focus) { terakhir.focus(); }
    }
    function tombolKey(e) {
      if (e.key === 'Escape') { e.preventDefault(); tutupDialog(); }
      if (e.key === 'Tab') {
        e.preventDefault();
        (document.activeElement === lanjut ? batal : lanjut).focus();
      }
    }
    document.addEventListener('keydown', tombolKey, true);
    batal.addEventListener('click', tutupDialog);
    latar.addEventListener('mousedown', function (e) { if (e.target === latar) { tutupDialog(); } });
    lanjut.addEventListener('click', function () { tutupDialog(); setuju(); });
  }

  document.querySelectorAll('form[data-konfirmasi]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (f.getAttribute('data-sudah-konfirmasi') === '1') { return; }
      e.preventDefault();
      var pengirim = e.submitter || null;
      var bahaya = !!f.querySelector('.btn-danger, .bahaya');
      tanyaKonfirmasi(f.getAttribute('data-konfirmasi'), bahaya, function () {
        f.setAttribute('data-sudah-konfirmasi', '1');
        if (f.requestSubmit) { f.requestSubmit(pengirim || undefined); } else { f.submit(); }
      });
    });
  });

  // 2b. Pesan umpan balik: dapat ditutup, pesan sukses hilang sendiri
  document.querySelectorAll('.flash .flash-tutup').forEach(function (b) {
    var kotak = b.closest('.flash');
    b.addEventListener('click', function () { kotak.remove(); });
    if (kotak.classList.contains('flash-ok')) { setTimeout(function () { if (kotak.parentNode) { kotak.remove(); } }, 6000); }
  });

  // 3. Salin nomor surat ke papan klip
  var salin = document.getElementById('salin-nomor');
  var teks = document.getElementById('nomor-teks');
  if (salin && teks) {
    salin.addEventListener('click', function () {
      var nilai = teks.textContent.trim();
      var selesai = function () {
        salin.textContent = 'Tersalin!';
        setTimeout(function () { salin.textContent = 'Salin nomor'; }, 1800);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(nilai).then(selesai, function () {
          window.prompt('Salin nomor surat:', nilai);
        });
      } else {
        window.prompt('Salin nomor surat:', nilai);
      }
    });
  }

  // 4. Cegah pengiriman ganda pada formulir
  document.querySelectorAll('form[action$="ambil/proses"], form#form-surat').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var b = f.querySelector('button[type=submit]');
      if (b && !e.defaultPrevented) {
        setTimeout(function () { b.disabled = true; }, 0);
      }
    });
  });

  // 5. Interaksi Formulir Surat Tugas
  var formSurat = document.getElementById('form-surat');
  if (formSurat) {
    var rawPeran = formSurat.getAttribute('data-peran');
    var listPeran = [];
    try {
      listPeran = rawPeran ? JSON.parse(rawPeran) : [];
    } catch (e) {
      listPeran = [];
    }

    // --- Kelola Tanggal Pelaksanaan ---
    var containerTgl = document.getElementById('baris-tanggal');
    var btnTambahTgl = document.getElementById('tambah-tanggal');
    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    function pecahTanggal(v) {
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
      return m ? { y: +m[1], m: +m[2], d: +m[3] } : null;
    }
    function teksTanggal(t) { return t.d + ' ' + BULAN[t.m - 1] + ' ' + t.y; }

    // Menuliskan tanggal seperti pada surat (sama dengan rentang_tanggal() di server)
    function formatRentang(daftar) {
      daftar.sort(function (a, b) { return a[0] < b[0] ? -1 : (a[0] > b[0] ? 1 : 0); });
      var kelompok = {}, urut = [];
      daftar.forEach(function (r) {
        var a = pecahTanggal(r[0]), z = pecahTanggal(r[1]);
        if (a.y === z.y && a.m === z.m) {
          var k = a.y + '-' + a.m;
          if (!kelompok[k]) { kelompok[k] = { akhir: BULAN[a.m - 1] + ' ' + a.y, butir: [] }; urut.push(k); }
          kelompok[k].butir.push(r[0] === r[1] ? String(a.d) : a.d + ' s.d. ' + z.d);
        } else {
          var kl = 'lintas' + urut.length;
          kelompok[kl] = { akhir: '', butir: [a.y === z.y
            ? a.d + ' ' + BULAN[a.m - 1] + ' s.d. ' + z.d + ' ' + BULAN[z.m - 1] + ' ' + z.y
            : teksTanggal(a) + ' s.d. ' + teksTanggal(z)] };
          urut.push(kl);
        }
      });
      return urut.map(function (k) {
        var b = kelompok[k].butir;
        var teks = b.length > 1 ? b.slice(0, -1).join(', ') + ' dan ' + b[b.length - 1] : b[0];
        return (teks + ' ' + kelompok[k].akhir).trim();
      }).join('; ');
    }

    function perbaruiPratinjau() {
      var kotak = document.getElementById('pratinjau-tanggal');
      var isi = document.getElementById('pratinjau-isi');
      if (!kotak || !isi || !containerTgl) { return; }
      var daftar = [];
      containerTgl.querySelectorAll('.baris-tanggal').forEach(function (b) {
        var m = b.querySelector('input[name="mulai[]"]').value;
        var s = b.querySelector('input[name="selesai[]"]').value || m;
        if (pecahTanggal(m) && pecahTanggal(s) && s >= m) { daftar.push([m, s]); }
      });
      kotak.hidden = daftar.length === 0;
      if (daftar.length) { isi.textContent = formatRentang(daftar); }
    }

    function bindBarisTanggal(baris) {
      var inputMulai = baris.querySelector('input[name="mulai[]"]');
      var inputSelesai = baris.querySelector('input[name="selesai[]"]');
      var btnHapus = baris.querySelector('.hapus-tanggal');

      if (inputMulai && inputSelesai) {
        // "Sampai tanggal" tidak boleh lebih awal dari tanggal mulai
        inputMulai.addEventListener('change', function () {
          inputSelesai.min = inputMulai.value || '';
          if (inputSelesai.value && inputSelesai.value < inputMulai.value) { inputSelesai.value = ''; }
        });
        inputSelesai.min = inputMulai.value || '';
      }

      if (btnHapus) {
        btnHapus.addEventListener('click', function () {
          var semuaBaris = containerTgl.querySelectorAll('.baris-tanggal');
          if (semuaBaris.length > 1) {
            baris.remove();
          } else {
            if (inputMulai) inputMulai.value = '';
            if (inputSelesai) inputSelesai.value = '';
          }
          perbaruiPratinjau();
        });
      }
    }

    function tambahBarisTanggal(mulai, selesai) {
      var div = document.createElement('div');
      div.className = 'baris-tanggal';
      div.innerHTML =
        '<label class="field"><span>Tanggal mulai</span><input type="date" name="mulai[]" value=""></label>' +
        '<label class="field"><span>Sampai tanggal <small>(kosongkan bila satu hari)</small></span><input type="date" name="selesai[]" value=""></label>' +
        '<button type="button" class="btn btn-ghost hapus-tanggal" title="Hapus baris tanggal ini"><svg width="18" height="18" class="ik" aria-hidden="true"><use href="#i-hapus"></use></svg>Hapus</button>';
      div.querySelector('input[name="mulai[]"]').value = mulai || '';
      div.querySelector('input[name="selesai[]"]').value = selesai || '';
      containerTgl.appendChild(div);
      bindBarisTanggal(div);
      return div;
    }

    if (containerTgl) {
      containerTgl.querySelectorAll('.baris-tanggal').forEach(bindBarisTanggal);
      containerTgl.addEventListener('input', perbaruiPratinjau);
      containerTgl.addEventListener('change', perbaruiPratinjau);
      perbaruiPratinjau();
    }

    if (btnTambahTgl && containerTgl) {
      btnTambahTgl.addEventListener('click', function () {
        var div = tambahBarisTanggal('', '');
        div.querySelector('input[name="mulai[]"]').focus();
      });
    }

    // Tanggal terpisah: "4, 7, 9" pada bulan tertentu menjadi tiga baris satu hari
    var tpTambah = document.getElementById('tp-tambah');
    if (tpTambah && containerTgl) {
      tpTambah.addEventListener('click', function () {
        var bulan = document.getElementById('tp-bulan').value;
        var hari = document.getElementById('tp-hari');
        var pesan = document.getElementById('tp-pesan');
        var bm = /^(\d{4})-(\d{2})$/.exec(bulan);
        if (!bm) { pesan.textContent = 'Pilih bulan terlebih dahulu.'; return; }
        var maks = new Date(+bm[1], +bm[2], 0).getDate();
        var angka = (hari.value.match(/\d+/g) || []).map(Number);
        var unik = angka.filter(function (n, i) { return angka.indexOf(n) === i; });
        var salah = unik.filter(function (n) { return n < 1 || n > maks; });
        if (!unik.length) { pesan.textContent = 'Isi tanggalnya, misalnya 4, 7, 9.'; return; }
        if (salah.length) { pesan.textContent = 'Bulan ini hanya sampai tanggal ' + maks + '. Periksa: ' + salah.join(', ') + '.'; return; }
        // baris pertama yang masih kosong dipakai lebih dulu
        var kosong = Array.prototype.filter.call(containerTgl.querySelectorAll('.baris-tanggal'), function (b) {
          return !b.querySelector('input[name="mulai[]"]').value && !b.querySelector('input[name="selesai[]"]').value;
        });
        kosong.forEach(function (b) { b.remove(); });
        unik.sort(function (a, b) { return a - b; }).forEach(function (n) {
          var tgl = bm[1] + '-' + bm[2] + '-' + (n < 10 ? '0' : '') + n;
          tambahBarisTanggal(tgl, '');
        });
        hari.value = '';
        pesan.textContent = unik.length + ' tanggal ditambahkan.';
        perbaruiPratinjau();
      });
    }

    // --- Kelola Penerima / Pegawai yang Ditugaskan ---
    var cariInput = document.getElementById('cari-pegawai');
    var hasilCari = document.getElementById('hasil-cari');
    var daftarPenerima = document.getElementById('daftar-penerima');
    var penerimaKosong = document.getElementById('penerima-kosong');
    var blokMassal = document.getElementById('blok-massal');

    function updateStatusPenerima() {
      var jumlah = daftarPenerima ? daftarPenerima.querySelectorAll('.baris-penerima').length : 0;
      if (penerimaKosong) {
        penerimaKosong.hidden = jumlah > 0;
      }
      if (blokMassal) {
        blokMassal.hidden = jumlah < 2;
      }
    }

    function hapusPenerima(baris) {
      baris.remove();
      updateStatusPenerima();
    }

    if (daftarPenerima) {
      daftarPenerima.querySelectorAll('.baris-penerima').forEach(function (baris) {
        var btnHapus = baris.querySelector('.hapus');
        if (btnHapus) {
          btnHapus.addEventListener('click', function () {
            hapusPenerima(baris);
          });
        }
      });
      updateStatusPenerima();
    }

    function tambahPenerimaKeDaftar(pegawai) {
      if (!daftarPenerima) return;
      var sudahAda = daftarPenerima.querySelector('.baris-penerima[data-id="' + pegawai.id + '"]');
      if (sudahAda) {
        sudahAda.style.transition = 'background-color 0.3s';
        sudahAda.style.backgroundColor = 'var(--aksen-muda)';
        setTimeout(function () { sudahAda.style.backgroundColor = ''; }, 1000);
        return;
      }

      var li = document.createElement('li');
      li.className = 'baris-penerima';
      li.setAttribute('data-id', pegawai.id);

      var subInfo = [];
      if (pegawai.nip) subInfo.push(pegawai.nip);
      if (pegawai.pangkat_golongan) subInfo.push(pegawai.pangkat_golongan);
      if (pegawai.jabatan) subInfo.push(pegawai.jabatan);

      var html = '<input type="hidden" name="pegawai_id[]" value="' + pegawai.id + '">' +
        '<div class="info"><strong>' + escapeHtml(pegawai.nama) + '</strong>' +
        '<span>' + escapeHtml(subInfo.join(' | ')) + '</span></div>' +
        '<select name="peran_id[]" aria-label="Peran ' + escapeHtml(pegawai.nama) + '">' +
        '<option value="">Tanpa peran</option>';

      listPeran.forEach(function (pr) {
        html += '<option value="' + pr.id + '">' + escapeHtml(pr.nama) + '</option>';
      });

      html += '</select><button type="button" class="btn btn-ghost hapus">Hapus</button>';

      li.innerHTML = html;
      daftarPenerima.appendChild(li);

      var btnHapus = li.querySelector('.hapus');
      if (btnHapus) {
        btnHapus.addEventListener('click', function () {
          hapusPenerima(li);
        });
      }

      updateStatusPenerima();
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // Pencarian pegawai dengan auto-complete
    var timerCari = null;
    var indeksSorot = -1;

    function sembunyikanHasilCari() {
      if (hasilCari) {
        hasilCari.hidden = true;
        hasilCari.innerHTML = '';
        indeksSorot = -1;
      }
    }

    function tampilkanSkeletonCari() {
      var html = '';
      for (var i = 0; i < 3; i++) {
        html += '<li class="skel-item" aria-hidden="true"><div class="skel skel-garis sedang"></div><div class="skel skel-garis pendek"></div></li>';
      }
      hasilCari.innerHTML = html;
      hasilCari.hidden = false;
      indeksSorot = -1;
    }

    if (cariInput && hasilCari) {
      cariInput.addEventListener('input', function () {
        clearTimeout(timerCari);
        var q = cariInput.value.trim();
        if (q.length < 2) {
          sembunyikanHasilCari();
          return;
        }

        timerCari = setTimeout(function () {
          tampilkanSkeletonCari();
          fetch(siteUrl('pegawai/cari?q=' + encodeURIComponent(q)), {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              hasilCari.innerHTML = '';
              indeksSorot = -1;
              if (!Array.isArray(data) || data.length === 0) {
                var liKosong = document.createElement('li');
                liKosong.className = 'kosong';
                liKosong.textContent = 'Pegawai tidak ditemukan. Tambahkan di menu Pegawai bila perlu.';
                hasilCari.appendChild(liKosong);
                hasilCari.hidden = false;
                return;
              }

              data.forEach(function (pegawai) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var info = [];
                if (pegawai.nip) info.push(pegawai.nip);
                if (pegawai.pangkat_golongan) info.push(pegawai.pangkat_golongan);
                if (pegawai.jabatan) info.push(pegawai.jabatan);

                li.innerHTML = '<strong>' + escapeHtml(pegawai.nama) + '</strong>' +
                  '<small>' + escapeHtml(info.join(' | ')) + '</small>';

                li.addEventListener('click', function () {
                  tambahPenerimaKeDaftar(pegawai);
                  cariInput.value = '';
                  sembunyikanHasilCari();
                  cariInput.focus();
                });

                hasilCari.appendChild(li);
              });
              hasilCari.hidden = false;
            })
            .catch(function () {
              sembunyikanHasilCari();
            });
        }, 220);
      });

      cariInput.addEventListener('keydown', function (e) {
        var items = hasilCari.querySelectorAll('li[role="option"]');
        if (hasilCari.hidden || items.length === 0) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          indeksSorot = Math.min(indeksSorot + 1, items.length - 1);
          updateSorot(items);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          indeksSorot = Math.max(indeksSorot - 1, 0);
          updateSorot(items);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (indeksSorot >= 0 && items[indeksSorot]) {
            items[indeksSorot].click();
          }
        } else if (e.key === 'Escape') {
          sembunyikanHasilCari();
        }
      });

      function updateSorot(items) {
        items.forEach(function (item, idx) {
          if (idx === indeksSorot) {
            item.setAttribute('aria-selected', 'true');
            item.scrollIntoView({ block: 'nearest' });
          } else {
            item.removeAttribute('aria-selected');
          }
        });
      }

      document.addEventListener('click', function (e) {
        if (!cariInput.contains(e.target) && !hasilCari.contains(e.target)) {
          sembunyikanHasilCari();
        }
      });
    }

    // --- Perubahan Tanggal Surat -> Perbarui Nomor Tersedia ---
    var inputTglSurat = document.getElementById('tanggal-surat');
    var inputNomorPilih = document.getElementById('nomor-pilih');
    var datalistNomor = document.getElementById('nomor-bebas');

    if (inputTglSurat && inputNomorPilih && datalistNomor) {
      inputTglSurat.addEventListener('change', function () {
        var tgl = inputTglSurat.value;
        if (!tgl || tgl.length < 4) return;
        var tahun = tgl.substring(0, 4);
        fetch(siteUrl('nomor/tersedia?tahun=' + encodeURIComponent(tahun)), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (res) { return res.json(); })
          .then(function (res) {
            if (res && res.nomor) {
              datalistNomor.innerHTML = '';
              res.nomor.forEach(function (n) {
                var opt = document.createElement('option');
                opt.value = n;
                datalistNomor.appendChild(opt);
              });
            }
            if (res && res.berikutnya) {
              inputNomorPilih.placeholder = 'Otomatis (B-' + res.berikutnya + ')';
            }
          })
          .catch(function () {});
      });
    }
  }
})();

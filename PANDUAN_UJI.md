# Panduan uji setelah memasang versi ini

Kode belum pernah dijalankan di server nyata saat dibuat. Ikuti urutan di bawah; setiap langkah menyebut hasil yang diharapkan.
Jika ada yang tidak sesuai, catat halaman, langkah, dan pesan error (atau isi `application/logs/log-*.php`), lalu kirim ke pembuatnya.

## 0. Persiapan
1. Timpa proyek dengan isi zip. Pastikan `application/storage/` dapat ditulis oleh web server.
2. Login sebagai **admin**, buka menu yang meminta **Pembaruan**, jalankan. Harus berhasil sampai selesai (versi 11).
3. Tekan **Ctrl+F5** di setiap halaman (CSS/JS diberi versi, tetapi tetap bersihkan cache sekali).

## 1. Tampilan dasar
- Login: kolom kiri biru, tombol mata menampilkan/menyembunyikan kata sandi, salah sandi memunculkan pesan merah.
- Sidebar: kelompok Utama, Persuratan, Kepegawaian, Laporan, Administrasi. Menu aktif tersorot.
- Layar kecil: tombol menu membuka drawer; klik area gelap menutupnya.

## 2. Pegawai
- Data & riwayat pegawai: daftar berisi 93 pegawai BPS Provinsi (setelah Pembaruan). Pegawai di luar daftar hilang atau nonaktif.
- Ikon jam pada satu pegawai membuka **Riwayat penugasan** (kartu ringkasan + tabel).

## 3. Surat tugas
- Buat surat: pilih pegawai, isi uraian. Uji tiga bentuk tanggal: (a) satu hari, (b) rentang, (c) kotak **Tanggal terpisah** dengan `4, 7, 9`.
  Pratinjau "Tertulis di surat" harus menampilkan `4, 7 dan 9 Oktober 2026`.
- Setelah terbit: tombol **Unduh Word (dapat diedit)** harus mengunduh .docx yang terbuka di Word dengan nama, tanggal, nomor terisi.
- Uji dengan satu penerima (tabel Kepada) dan beberapa penerima (tabel lampiran bertambah sesuai jumlah).
- Awalan "Untuk": coba "Menjadi Narasumber pada Kegiatan ..." dan "Melaksanakan perjalanan dinas dalam rangka ...". Kalimat pada Word harus mengikuti.
- PDF tetap dapat dibuka dari tombol **Buka PDF**.

## 4. Ambil nomor dan template
- Ambil nomor lalu **Unduh Word**: dokumen terbuka; tanda [kurung siku] boleh tersisa untuk diisi manual.
- Menu Template Word: tampil 14 jenis (satu dan banyak penerima) dengan versi lama dan baru.

## 5. Arsip berkas
- Pada detail surat / hasil ambil nomor / ubah register: unggah PDF, DOCX, JPG, atau PNG (maks 10 MB).
- Buka, unduh, dan hapus (dialog konfirmasi muncul). Berkas bernama acak tersimpan di `application/storage/arsip/`.

## 6. Cuti
- Admin: **Jenis cuti** — periksa arti KN, TL, CST, CP lalu ubah bila perlu.
- Admin: **Impor rekap cuti** — unduh satu sheet bulan sebagai CSV, pilih bulan/tahun, impor. Laporan hasil menampilkan nama yang dilewati.
- Catatan cuti: tambah, ubah, hapus. Catatan yang tanggalnya bertabrakan untuk pegawai yang sama harus ditolak.
- Kalender cuti: kode berwarna per tanggal; akhir pekan berlatar abu.
- Saldo cuti: ubah jatah/sisa; kolom Sisa berubah mengikuti catatan CT dan CST.

## 7. Hal yang sengaja belum ada
- Bangkom, buku ekspedisi, surat masuk, dashboard gabungan.

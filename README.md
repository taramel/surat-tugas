# Aplikasi Surat Tugas – BPS Provinsi Kalimantan Barat

Aplikasi web terpadu untuk pengelolaan, penomoran, penerbitan, dan pengarsipan **Surat Tugas** di lingkungan **Badan Pusat Statistik (BPS) Provinsi Kalimantan Barat**.

Dibangun menggunakan **CodeIgniter 3.1.13**, **MySQL/MariaDB**, dan **Dompdf** (ukuran kertas standar kedinasan F4 / Folio), aplikasi ini menggantikan dan menyempurnakan alur kerja manual *mail merge* Google Sheets/Docs sebelumnya menjadi satu sistem mandiri yang terstruktur, aman, dan mudah digunakan.

---

## 🌟 Fitur Utama

### 1. Penerbitan Surat Tugas (Single & Multi-Person)
- **Format Otomatis**:
  - Penugasan **1 orang**: Dokumen diterbitkan tanpa tabel (nama, NIP, pangkat, jabatan langsung pada badan surat).
  - Penugasan **>1 orang**: Dokumen diterbitkan lengkap dengan tabel lampiran daftar pegawai.
- **Pencarian Pegawai Real-Time**: Kolom pencarian interaktif (nama, NIP, atau jabatan) dengan *autocomplete* instan dan pemilihan peran penugasan.
- **Penugasan Massal (Opsi)**: Kemampuan menerbitkan 1 surat untuk masing-masing pegawai terpilih dengan nomor urut berurutan secara otomatis.
- **Rentang Tanggal Fleksibel**: Mendukung multi-rentang tanggal (contoh: kegiatan berselang seperti 16 April dan 20 April).

### 2. Output Dokumen Ganda (PDF Resmi & Microsoft Word)
- **Cetak PDF Instan**: Output PDF rapi sesuai tata naskah dinas BPS, menggunakan ukuran kertas **F4 / Folio (21,5 x 33 cm)**, kop BPS resmi, dan format nomor otomatis.
- **Template Microsoft Word (.docx)**: Dukungan pengunduhan dokumen Word siap pakai yang telah terisi penanda `{{NOMOR}}`, `{{TANGGAL}}`, `{{TEMPAT}}`, `{{URAIAN}}`, `{{TTD_NAMA}}`, `{{TTD_JABATAN}}`, dsb.
- **Multi-Cetak (Bulk PDF)**: Opsi mencetak atau mengunduh beberapa surat tugas sekaligus dalam satu file PDF.

### 3. Register & Penomoran Surat Anti-Ganda
- **Penomoran Atomik**: Penomoran diatur menggunakan *database locks* (`GET_LOCK`) sehingga dua pengguna yang menerbitkan surat bersamaan tidak akan mendapatkan nomor yang sama (*zero collision*).
- **Format Baku BPS**: `B-{urut}/61513/KP.650/{tahun}` (dapat disesuaikan di menu Pengaturan).
- **Visualisasi Peta Nomor**: Peta kotak nomor interaktif yang menampilkan nomor terpakai, kosong, dibatalkan, atau tanpa uraian.
- **Ekspor Register**: Unduh seluruh riwayat register nomor ke format CSV.

### 4. Manajemen Data Pegawai
- Master data pegawai BPS Provinsi Kalimantan Barat (NIP, nama, pangkat/golongan, jabatan, unit kerja).
- Fitur **Impor CSV** massal untuk memperbarui data kepegawaian sewaktu-waktu.
- Toggle status aktif/nonaktif pegawai tanpa merusak riwayat surat terdahulu.

### 5. Impor Riwayat Mail Merge & Register Lama
- **Impor Riwayat Surat**: Modul khusus untuk membaca data mentah dari spreadsheet Google Drive/Mail Merge lama (termasuk penyimpanan URL arsip Google Docs dokumen asli).
- **Impor Register Lama**: Mendukung unggahan berkas Excel (.xlsx) atau CSV sheet agenda surat tugas lama.

### 6. Pengelolaan Fleksibel (Admin Master)
- **Pejabat Penandatangan**: Manajemen pejabat penandatangan dengan masa berlaku (contoh: Kepala BPS Prov Kalbar, Plh, dsb.).
- **Peran Penugasan**: Manajemen peran seperti *Peserta*, *Pengawas*, *Narasumber*, *Panitia*, *Mentor*, *Innas*, *Inda*, *Admin Bangkom*.
- **Dasar Hukum ("Mengingat")**: Manajemen versi dasar hukum per tanggal berlaku (misalnya penyesuaian Peraturan BPS Nomor 5 Tahun 2023 hingga 7 Juni 2026, dan Peraturan BPS Nomor 3 Tahun 2026 mulai 8 Juni 2026). Surat lama tetap mencetak dasar hukum yang berlaku pada masanya.
- **Pengaturan Kop & Redaksi**: Kustomisasi teks kop, kalimat menimbang, dan kalimat penugasan langsung dari panel web.

### 7. Keamanan & Audit
- Proteksi **CSRF (Cross-Site Request Forgery)** pada setiap formulir POST.
- Penyimpanan kata sandi aman (*bcrypt*).
- Pembatasan percobaan login gagal (*brute-force throttling*).
- Header keamanan (*X-Frame-Options*, *X-Content-Type-Options*, *CSP*).
- Log aktivitas lengkap untuk audit setiap aksi pengguna.

---

## 💻 Kebutuhan Sistem

- **PHP**: 7.4, 8.0, 8.1, 8.2, atau 8.3
  - Ekstensi wajib: `mysqli`, `mbstring`, `dom`, `zip`, `gd`
- **Database**: MySQL 5.7+ atau MariaDB 10.3+
- **Web Server**: Apache / Nginx / Laragon / XAMPP, atau *built-in web server* PHP

---

## 🚀 Panduan Instalasi Cepat

### 1. Letakkan Proyek
Salin folder proyek ke direktori web server Anda, misalnya:
- XAMPP: `C:\xampp\htdocs\surat-tugas`
- Laragon: `C:\laragon\www\surat-tugas`

### 2. Siapkan Database
1. Buka MySQL Client / phpMyAdmin.
2. Buat database atau langsung impor file skema:
   ```bash
   mysql -u root -p < database/surat_tugas.sql
   ```
   > [!NOTE]
   > File `database/surat_tugas.sql` sudah lengkap berisi 12 tabel, konfigurasi default, data penandatangan, 8 peran standar, dasar hukum dinamis, dan **31 data pegawai BPS Provinsi Kalimantan Barat**.

### 3. Konfigurasi Koneksi Database
Buka file `application/config/database.php` dan sesuaikan kredensial database Anda bila berbeda dari bawaan:
```php
'hostname' => getenv('DB_HOST') ?: 'localhost',
'username' => getenv('DB_USER') ?: 'root',
'password' => getenv('DB_PASS') ?: '',
'database' => getenv('DB_NAME') ?: 'surat_tugas',
```

### 4. Izin Tulis Direktori
Pastikan folder berikut memiliki izin tulis (*writeable*):
- `application/cache/sessions`
- `application/cache/impor`
- `application/storage/template`
- `application/logs`

### 5. Masuk ke Aplikasi
1. Buka browser dan arahkan ke: `http://localhost/surat-tugas/`
2. Kredensial akun administrator awal:
   - **Username**: `admin`
   - **Password**: `Admin#2026`
3. Saat pertama kali masuk, sistem akan mewajibkan penggantian kata sandi demi keamanan.

---

## 📁 Struktur Direktori

```
surat-tugas/
├── application/
│   ├── config/          # Konfigurasi database, routes, autoload
│   ├── controllers/     # Controller (Surat, Pegawai, Ambil, Register, Laporan, Master, Auth, dll.)
│   ├── core/            # MY_Controller (otentikasi & kontrol sesi)
│   ├── helpers/         # app_helper.php, impor_helper.php
│   ├── libraries/       # Pdf.php (Dompdf F4), Docx_isi.php, Skema.php, Xlsx_reader.php
│   ├── models/          # Surat_model, Pegawai_model, Nomor_model, Master_model, dll.
│   ├── storage/         # Template dokumen Word bawaan & unggahan admin
│   └── views/           # Antarmuka pengguna (Dashboard, Surat, Pegawai, Laporan, dll.)
├── assets/
│   ├── css/app.css      # Styling responsif dengan palet warna resmi BPS
│   ├── js/app.js        # Script interaktif (pencarian pegawai, multi-tanggal, salin nomor)
│   └── img/logo.png     # Logo resmi BPS
├── database/
│   └── surat_tugas.sql  # Skema lengkap versi 7 & data awal siap pakai
└── tests/               # Unit testing helper & parser
```

---

## 👥 Pengguna & Hak Akses

| Peran | Kemampuan |
|---|---|
| **Operator** | Menerbitkan surat tugas, mengambil nomor cepat, mengunduh file Word & PDF, melihat & mencari di register nomor, membatalkan nomor miliknya, melihat data pegawai. |
| **Admin** | Seluruh hak akses Operator, ditambah: manajemen pengguna, manajemen pegawai (tambah, ubah, impor), manajemen penandatangan, pengaturan dasar hukum & kop surat, pemulihan nomor/surat yang dibatalkan, impor riwayat lama, dan unduh cadangan database. |

-- ============================================================================
-- Aplikasi Surat Tugas – BPS Provinsi Kalimantan Barat
-- Skema dan data awal (versi database 7)
--
-- Pemasangan baru: impor berkas ini saja.
--   mysql -u root -p < database/surat_tugas.sql
--
-- Berkas ini menggantikan seluruh skema dan migrasi sebelumnya.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS surat_tugas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE surat_tugas;

-- ============================================================================
-- TABEL INTI
-- ============================================================================

-- --------------------------------------------------------------------------
-- Pengguna aplikasi
-- --------------------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Pejabat penandatangan surat tugas
-- --------------------------------------------------------------------------
CREATE TABLE penandatangan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    nip VARCHAR(30) NULL,
    jabatan VARCHAR(200) NOT NULL,
    aktif_dari DATE NOT NULL,
    aktif_sampai DATE NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Pengaturan global aplikasi (key-value)
-- --------------------------------------------------------------------------
CREATE TABLE pengaturan (
    kunci VARCHAR(50) PRIMARY KEY,
    nilai TEXT NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Data pegawai BPS Provinsi Kalimantan Barat
-- --------------------------------------------------------------------------
CREATE TABLE pegawai (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    pangkat_golongan VARCHAR(100) NULL,
    jabatan VARCHAR(200) NOT NULL,
    unit_kerja VARCHAR(200) NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Peran pegawai dalam surat tugas (Peserta, Pengawas, Narasumber, dsb.)
-- --------------------------------------------------------------------------
CREATE TABLE peran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Dasar hukum (bagian "Mengingat") – berlaku berdasarkan tanggal
-- --------------------------------------------------------------------------
CREATE TABLE dasar_hukum (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    berlaku_mulai DATE NOT NULL,
    keterangan VARCHAR(150) NULL,
    mengingat TEXT NOT NULL,
    UNIQUE KEY uq_dasar_mulai (berlaku_mulai)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Template Word. Berkas dengan nama yang sama membentuk satu deret versi;
-- yang dipakai adalah versi dengan tanggal mulai berlaku terbaru yang tidak
-- melewati tanggal surat.
-- --------------------------------------------------------------------------
CREATE TABLE template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    berlaku_mulai DATE NOT NULL,
    berkas VARCHAR(80) NOT NULL,
    catatan VARCHAR(300) NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    diunggah_oleh INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_nama (nama, berlaku_mulai)
) ENGINE=InnoDB;

-- ============================================================================
-- TABEL SURAT TUGAS
-- ============================================================================

-- --------------------------------------------------------------------------
-- Surat tugas utama
-- --------------------------------------------------------------------------
CREATE TABLE surat_tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun SMALLINT UNSIGNED NOT NULL,
    nomor_urut INT UNSIGNED NOT NULL,
    nomor_surat VARCHAR(100) NOT NULL,
    tanggal_surat DATE NOT NULL,
    tempat VARCHAR(100) NOT NULL DEFAULT 'Pontianak',
    asal ENUM('internal','eksternal') NOT NULL DEFAULT 'eksternal',
    unit_pemohon VARCHAR(200) NULL,
    rujukan_pengirim VARCHAR(200) NULL,
    rujukan_nomor VARCHAR(200) NULL,
    rujukan_hal VARCHAR(300) NULL,
    menimbang_a TEXT NULL,
    untuk TEXT NOT NULL,
    penandatangan_id INT UNSIGNED NULL,
    status ENUM('aktif','dibatalkan') NOT NULL DEFAULT 'aktif',
    sumber ENUM('aplikasi','impor') NOT NULL DEFAULT 'aplikasi',
    tautan_arsip VARCHAR(400) NULL,
    alasan_batal VARCHAR(300) NULL,
    catatan VARCHAR(300) NULL,
    dibuat_oleh INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_surat (tahun, nomor_urut),
    INDEX idx_surat_tahun_status (tahun, status),
    INDEX idx_surat_tanggal (tanggal_surat),
    CONSTRAINT fk_surat_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_surat_ttd FOREIGN KEY (penandatangan_id) REFERENCES penandatangan(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Pegawai yang ditugaskan dalam surat tugas (snapshot data saat surat dibuat)
-- --------------------------------------------------------------------------
CREATE TABLE surat_tugas_pegawai (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_tugas_id INT UNSIGNED NOT NULL,
    pegawai_id INT UNSIGNED NULL,
    peran_id INT UNSIGNED NULL,
    urutan TINYINT UNSIGNED NOT NULL DEFAULT 1,
    nama VARCHAR(150) NOT NULL,
    nip VARCHAR(30) NULL,
    pangkat_golongan VARCHAR(100) NULL,
    jabatan VARCHAR(200) NULL,
    unit_kerja VARCHAR(200) NULL,
    INDEX idx_stp_surat (surat_tugas_id),
    INDEX idx_stp_pegawai (pegawai_id),
    CONSTRAINT fk_stp_surat FOREIGN KEY (surat_tugas_id) REFERENCES surat_tugas(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_stp_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_stp_peran FOREIGN KEY (peran_id) REFERENCES peran(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------------------------
-- Rentang tanggal pelaksanaan tugas
-- --------------------------------------------------------------------------
CREATE TABLE surat_tugas_tanggal (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_tugas_id INT UNSIGNED NOT NULL,
    mulai DATE NOT NULL,
    selesai DATE NOT NULL,
    INDEX idx_stt_surat (surat_tugas_id),
    CONSTRAINT fk_stt_surat FOREIGN KEY (surat_tugas_id) REFERENCES surat_tugas(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- REGISTER NOMOR SURAT
-- ============================================================================

-- --------------------------------------------------------------------------
-- Register nomor: satu baris per nomor urut per tahun.
-- Nomor tanpa baris dianggap kosong dan boleh dipakai.
-- --------------------------------------------------------------------------
CREATE TABLE nomor_surat (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun SMALLINT UNSIGNED NOT NULL,
    nomor_urut INT UNSIGNED NOT NULL,
    nomor_surat VARCHAR(100) NULL,
    status ENUM('tersedia','terpakai','dibatalkan','arsip') NOT NULL DEFAULT 'tersedia',
    uraian TEXT NULL,
    pembuat VARCHAR(100) NULL,
    dibuat_oleh INT UNSIGNED NULL,
    tanggal_surat DATE NULL,
    template_nama VARCHAR(150) NULL,
    penandatangan_id INT UNSIGNED NULL,
    surat_tugas_id INT UNSIGNED NULL,
    tautan_arsip VARCHAR(400) NULL,
    alasan_batal VARCHAR(300) NULL,
    nomor_asli VARCHAR(120) NULL,
    catatan VARCHAR(300) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nomor (tahun, nomor_urut),
    INDEX idx_nomor_status (status),
    INDEX idx_nomor_tanggal (tanggal_surat),
    INDEX idx_nomor_surat_tugas (surat_tugas_id),
    CONSTRAINT fk_nomor_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id),
    CONSTRAINT fk_nomor_ttd FOREIGN KEY (penandatangan_id) REFERENCES penandatangan(id),
    CONSTRAINT fk_nomor_surat FOREIGN KEY (surat_tugas_id) REFERENCES surat_tugas(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================================
-- LOG AKTIVITAS
-- ============================================================================

-- --------------------------------------------------------------------------
-- Catatan aktivitas pengguna untuk keperluan audit
-- --------------------------------------------------------------------------
CREATE TABLE log_aktivitas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    aksi VARCHAR(60) NOT NULL,
    keterangan TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_ip (aksi, ip, created_at)
) ENGINE=InnoDB;

-- ============================================================================
-- DATA AWAL
-- ============================================================================

-- --------------------------------------------------------------------------
-- Akun awal: username admin, kata sandi Admin#2026
-- Aplikasi mewajibkan penggantian saat masuk pertama kali.
-- --------------------------------------------------------------------------
INSERT INTO users (username, nama, password_hash, role, wajib_ganti_sandi) VALUES
('admin', 'Administrator', '$2y$10$ctQAegD4VEmFciNXxpR8YeFkQ.m8NkkkRzrJkOK6lmpvsIxa2WbFS', 'admin', 1);

-- --------------------------------------------------------------------------
-- Pejabat penandatangan bawaan
-- --------------------------------------------------------------------------
INSERT INTO penandatangan (nama, jabatan, aktif_dari) VALUES
('Muh Saichudin', 'Kepala Badan Pusat Statistik Provinsi Kalimantan Barat', '2024-01-01');

-- --------------------------------------------------------------------------
-- Peran bawaan untuk pegawai dalam surat tugas
-- --------------------------------------------------------------------------
INSERT INTO peran (nama) VALUES
('Peserta'),
('Pengawas'),
('Narasumber'),
('Panitia'),
('Mentor'),
('Innas'),
('Inda'),
('Admin Bangkom');

-- --------------------------------------------------------------------------
-- Dasar hukum (bagian "Mengingat") per periode berlaku
-- --------------------------------------------------------------------------
INSERT INTO dasar_hukum (berlaku_mulai, keterangan, mengingat) VALUES
('2000-01-01', 'Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)',
'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;\r\nPeraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;\r\nPeraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;\r\nPeraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);\r\nPeraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja BPS Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2023 Nomor 429), sebagaimana telah diubah dengan Peraturan BPS Nomor 3 Tahun 2025 tentang Perubahan atas Peraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2025 Nomor 683);'),
('2026-06-08', 'Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)',
'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;\r\nPeraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;\r\nPeraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;\r\nPeraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);\r\nPeraturan Badan Pusat Statistik Nomor 3 Tahun 2026 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan Badan Pusat Statistik Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2026 Nomor 331);');

-- --------------------------------------------------------------------------
-- Pengaturan bawaan aplikasi
-- --------------------------------------------------------------------------
INSERT INTO pengaturan (kunci, nilai) VALUES
('kode_satker', '61513'),
('kode_klasifikasi', '650'),
('format_nomor', 'B-{urut}/{satker}/KP.{klas}/{tahun}'),
('tempat_default', 'Pontianak'),
('versi_skema', '7'),
('awalan_untuk', 'Mengikuti Kegiatan'),
('kop_baris1', 'BADAN PUSAT STATISTIK'),
('kop_baris2', 'PROVINSI KALIMANTAN BARAT'),
('menimbang_a', 'bahwa merujuk surat dari {pengirim} nomor {nomor} hal {hal}, maka dipandang perlu menyesuaikan dengan jadwal kegiatan tersebut;'),
('menimbang_b', 'bahwa untuk pelaksanaannya perlu dikeluarkan Surat Tugas Kepala BPS Provinsi Kalimantan Barat;'),
('kalimat_memberi_tugas', 'Memberi Tugas:');

-- --------------------------------------------------------------------------
-- Data awal pegawai BPS Provinsi Kalimantan Barat
-- --------------------------------------------------------------------------
INSERT INTO pegawai (nip, nama, pangkat_golongan, jabatan, unit_kerja, aktif) VALUES
('197109071992111001', 'Muh Saichudin, S.Si, M.Si', 'Pembina Utama Muda - IV/c', 'Kepala BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('196603161992031003', 'Sukwantono, S.E. M.M.', 'Pembina Utama Muda - IV/c', 'Statistisi Ahli Madya BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('196703211992032002', 'Sari Mariani, S.E.', 'Pembina Utama Muda - IV/c', 'Statistisi Ahli Madya BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('197809012000122001', 'Dr. Preatin, SST., M.Si', 'Pembina Utama Muda - IV/c', 'Statistisi Ahli Madya BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('197911262002121006', 'Imam Setia Harnomo, SST, M.Stat', 'Pembina Tk. I - IV/b', 'Kepala BPS Kabupaten Sekadau', 'BPS Provinsi Kalimantan Barat', 1),
('197612121999032001', 'Heny Sucihati, S.ST., ME.', 'Pembina - IV/a', 'Statistisi Ahli Madya BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('198011212003122004', 'Fatmawati, S.S.T., M.E.', 'Pembina - IV/a', 'Statistisi Ahli Madya BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('198405202007011002', 'Nufi Alabshar, SST, M.Sc', 'Pembina - IV/a', 'Staf BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('198603272009021005', 'Supandi, S.Si, M.Ec.Dev.', 'Pembina - IV/a', 'Statistisi Ahli Madya BPS Kabupaten Mempawah', 'BPS Provinsi Kalimantan Barat', 1),
('198912192012112001', 'Cynthia Minanda, S.S.T.', 'Penata Tk. I - III/d', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199306062016021001', 'Arief Yuandi, S.S.T.', 'Penata Tk. I - III/d', 'Pranata Komputer Ahli Muda BPS Kabupaten Mempawah', 'BPS Provinsi Kalimantan Barat', 1),
('198008112005021004', 'Muhammad Adwin, S.Mn.', 'Penata - III/c', 'Kepala Subbagian Umum Kabupaten Mempawah', 'BPS Provinsi Kalimantan Barat', 1),
('198011042005021001', 'Ibrahim', 'Penata - III/c', 'Pelaksana', 'BPS Provinsi Kalimantan Barat', 1),
('198406082011011011', 'Yanis Munandar, S.E.', 'Penata - III/c', 'Analis Pengelolaan Keuangan APBN Ahli Pertama Bagian Umum', 'BPS Provinsi Kalimantan Barat', 1),
('198504182012121001', 'Jaka Eben Heizer, S.E, M.E, M.I.D.S', 'Penata - III/c', 'Statistisi Ahli Muda BPS Kota Singkawang', 'BPS Provinsi Kalimantan Barat', 1),
('198703202006042002', 'Martini Pratiwi, S.S.T.', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('198902172013111001', 'Ardi Atmaja, S.S.T., M.Ec.Dev.', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199011072014102002', 'Irene Novita, S.S.T., M.E.K.K.', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199111202014101001', 'Angga Fajar Sukma Alfariz, S.S.T.', 'Penata - III/c', 'Pranata Komputer Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199212212017011001', 'Romulus Arjuna Nugraha Tambunan', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199405122017012001', 'Ade Wahyu Pratama Putri, S.S.T.', 'Penata - III/c', 'Kepala Subbagian Umum Kabupaten Sambas', 'BPS Provinsi Kalimantan Barat', 1),
('199405192016021001', 'Risky, S.S.T.', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199502212017011001', 'Joko Pranoto, S.S.T.', 'Penata - III/c', 'Statistisi Ahli Muda BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('196806051994011002', 'Muklas Arianto', 'Penata Muda Tk. I - III/b', 'Statistisi Mahir BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199011232016021001', 'Idrus Efendy, SST', 'Penata Muda Tk. I - III/b', 'Pranata Komputer Ahli Pertama BPS Kabupaten Kubu Raya', 'BPS Provinsi Kalimantan Barat', 1),
('199303072018021001', 'Rizky Indra Permana, S.S.T.', 'Penata Muda Tk. I - III/b', 'Statistisi Ahli Pertama BPS Kabupaten Kayong Utara', 'BPS Provinsi Kalimantan Barat', 1),
('199610042019012002', 'Wahyu Nuraini, S.Tr.Stat', 'Penata Muda Tk. I - III/b', 'Statistisi Ahli Pertama BPS Kabupaten Kubu Raya', 'BPS Provinsi Kalimantan Barat', 1),
('199702072019011001', 'Handika Ramadhan, S.Tr.Stat.', 'Penata Muda Tk. I - III/b', 'Statistisi Ahli Pertama BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199804242022012001', 'Sifa Rofatunnisa, S.Tr.Stat.', 'Penata Muda Tk. I - III/b', 'Statistisi Ahli Pertama BPS Provinsi Kalimantan Barat', 'BPS Provinsi Kalimantan Barat', 1),
('199007302025062002', 'Nurdila Yulianti, S.Psi.', 'Penata Muda - III/a', 'Asesor SDM Aparatur Ahli Pertama Bagian Umum', 'BPS Provinsi Kalimantan Barat', 1),
('200106022023102004', 'Siti Fenisha Indah Puteri, S.Tr.Stat.', 'Penata Muda - III/a', 'Statistisi Ahli Pertama BPS Kota Pontianak', 'BPS Provinsi Kalimantan Barat', 1)
ON DUPLICATE KEY UPDATE nama = VALUES(nama), pangkat_golongan = VALUES(pangkat_golongan), jabatan = VALUES(jabatan);

-- ============================================================================
-- Selesai. Seluruh tabel dan data awal telah terpasang.
-- ============================================================================

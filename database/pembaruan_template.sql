-- Pembaruan template surat (format Menimbang/Mengingat, lampiran, kertas F4).
-- Jalankan sekali pada database yang sudah terpasang:  mysql -u root -p surat_tugas < database/pembaruan_template.sql
USE surat_tugas;
INSERT INTO pengaturan (kunci, nilai) VALUES
('kode_satker', '61513'),
('kode_klasifikasi', '650'),
('format_nomor', 'B-{urut}/{satker}/KP.{klas}/{tahun}'),
('tempat_default', 'Pontianak'),
('kop_baris1', 'BADAN PUSAT STATISTIK'),
('kop_baris2', 'PROVINSI KALIMANTAN BARAT'),
('menimbang_a', 'bahwa merujuk surat dari {pengirim} nomor {nomor} hal {hal}, maka dipandang perlu menyesuaikan dengan jadwal kegiatan tersebut;'),
('menimbang_b', 'bahwa untuk pelaksanaannya perlu dikeluarkan Surat Tugas Kepala BPS Provinsi Kalimantan Barat;'),
('mengingat', 'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;
Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;
Peraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;
Peraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);
Peraturan Badan Pusat Statistik Nomor 3 Tahun 2026 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan Badan Pusat Statistik Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2026 Nomor 331);'),
('kalimat_memberi_tugas', 'Memberi Tugas:')
ON DUPLICATE KEY UPDATE nilai = VALUES(nilai);
DELETE FROM pengaturan WHERE kunci IN ('kalimat_pembuka', 'kalimat_penutup', 'kop_alamat');
INSERT IGNORE INTO peran (nama) VALUES ('Innas'), ('Inda'), ('Admin Bangkom');

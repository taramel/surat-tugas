-- Pembaruan agar cetakan sesuai surat asli: dasar hukum per tanggal dan kalimat Menimbang khusus.
-- Jalankan sekali pada database yang sudah terpasang:  mysql -u root -p surat_tugas < database/pembaruan_dokumen.sql
USE surat_tugas;
ALTER TABLE surat_tugas ADD COLUMN menimbang_a TEXT NULL AFTER rujukan_hal;
CREATE TABLE dasar_hukum (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    berlaku_mulai DATE NOT NULL,
    keterangan VARCHAR(150) NULL,
    mengingat TEXT NOT NULL,
    UNIQUE KEY uq_dasar_mulai (berlaku_mulai)
) ENGINE=InnoDB;
INSERT INTO dasar_hukum (berlaku_mulai, keterangan, mengingat) VALUES
('2000-01-01', 'Peraturan BPS Nomor 5 Tahun 2023 (hingga 7 Juni 2026)', 'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;
Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;
Peraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;
Peraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);
Peraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja BPS Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2023 Nomor 429), sebagaimana telah diubah dengan Peraturan BPS Nomor 3 Tahun 2025 tentang Perubahan atas Peraturan BPS Nomor 5 Tahun 2023 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan BPS Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2025 Nomor 683);'),
('2026-06-08', 'Peraturan BPS Nomor 3 Tahun 2026 (mulai 8 Juni 2026)', 'Undang-Undang Nomor 16 Tahun 1997 tentang Statistik;
Peraturan Pemerintah Nomor 51 Tahun 1999 tentang Penyelenggaraan Statistik;
Peraturan Pemerintah Nomor 45 Tahun 2013 tentang Tata Cara Pelaksanaan Anggaran Pendapatan dan Belanja Negara sebagaimana telah diubah dengan Peraturan Pemerintah 50 Tahun 2018;
Peraturan Presiden Republik Indonesia Nomor 86 Tahun 2007 tentang Badan Pusat Statistik (Lembaran Negara Republik Indonesia Tahun 2007 Nomor 139), sebagaimana telah diubah dengan Peraturan Presiden Nomor 1 Tahun 2025 (Lembaran Negara Republik Indonesia Tahun 2025 Nomor 4);
Peraturan Badan Pusat Statistik Nomor 3 Tahun 2026 tentang Organisasi dan Tata Kerja Badan Pusat Statistik Provinsi dan Badan Pusat Statistik Kabupaten/Kota (Berita Negara Republik Indonesia Tahun 2026 Nomor 331);');
DELETE FROM pengaturan WHERE kunci = 'mengingat';
INSERT IGNORE INTO peran (nama) VALUES ('Mentor');
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES ('awalan_untuk', 'Mengikuti Kegiatan');

-- Pembaruan untuk impor riwayat surat: penanda surat hasil impor, tautan arsip, dan penerima tanpa data pegawai.
-- Jalankan sekali pada database yang sudah terpasang:  mysql -u root -p surat_tugas < database/pembaruan_riwayat.sql
USE surat_tugas;
ALTER TABLE surat_tugas
    ADD COLUMN sumber ENUM('aplikasi','impor') NOT NULL DEFAULT 'aplikasi' AFTER status,
    ADD COLUMN tautan_arsip VARCHAR(400) NULL AFTER sumber;
ALTER TABLE surat_tugas_pegawai MODIFY pegawai_id INT UNSIGNED NULL;

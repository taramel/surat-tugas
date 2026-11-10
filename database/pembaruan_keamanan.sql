-- Pembaruan keamanan: kolom penanda wajib ganti kata sandi.
-- Jalankan sekali pada database yang sudah terpasang:  mysql -u root -p surat_tugas < database/pembaruan_keamanan.sql
USE surat_tugas;
ALTER TABLE users ADD COLUMN wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 0 AFTER aktif;
-- Opsional: paksa akun admin bawaan mengganti kata sandi pada masuk berikutnya
-- UPDATE users SET wajib_ganti_sandi = 1 WHERE username = 'admin';

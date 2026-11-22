-- Menonaktifkan kewajiban ganti kata sandi pada akun yang sudah terpasang.
-- Jalankan sekali: mysql -u root -p surat_tugas < database/pembaruan_nonaktif_wajib_sandi.sql
USE surat_tugas;
UPDATE users SET wajib_ganti_sandi = 0 WHERE wajib_ganti_sandi = 1;

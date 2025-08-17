-- Skrip SQL untuk refactor fitur Lembur
-- PENTING: Backup database Anda sebelum menjalankan skrip ini.

-- Langkah 1: Hapus foreign key constraint untuk Id_Lembur dari tabel DETAIL_GAJI
-- Catatan: Nama constraint 'detail_gaji_ibfk_5' mungkin berbeda di sistem Anda.
-- Jika Anda mendapatkan error, jalankan 'SHOW CREATE TABLE DETAIL_GAJI;' untuk menemukan nama yang benar.
ALTER TABLE DETAIL_GAJI DROP FOREIGN KEY detail_gaji_ibfk_5;

-- Langkah 2: Hapus kolom yang tidak lagi diperlukan dari DETAIL_GAJI
ALTER TABLE DETAIL_GAJI DROP COLUMN Id_Lembur;

-- Langkah 3: Hapus kolom yang tidak lagi diperlukan dari GAJI
ALTER TABLE GAJI DROP COLUMN Total_Lembur;

-- Langkah 4: Hapus tabel LEMBUR yang sudah tidak digunakan
DROP TABLE LEMBUR;

-- Langkah 5: Tambahkan kolom Uang_Lembur ke tabel PRESENSI
ALTER TABLE PRESENSI ADD COLUMN Uang_Lembur DECIMAL(12, 2) DEFAULT 0 AFTER Jam_Lembur;

-- Script untuk mengubah tabel DETAIL_GAJI
-- Agar kolom Id_Tunjangan, Id_Potongan, dan Id_Lembur tidak bernilai NULL

-- Langkah 1: Tambahkan data default ke tabel master jika belum ada
-- Tunjangan default
INSERT INTO TUNJANGAN (Id_Tunjangan, Nama_Tunjangan, Keterangan) 
SELECT 1, 'Tidak Ada Tunjangan', 'Default untuk tidak ada tunjangan'
WHERE NOT EXISTS (SELECT 1 FROM TUNJANGAN WHERE Id_Tunjangan = 1);

-- Potongan default  
INSERT INTO POTONGAN (Id_Potongan, Nama_Potongan, Tarif, Keterangan)
SELECT 1, 'Tidak Ada Potongan', 0, 'Default untuk tidak ada potongan'
WHERE NOT EXISTS (SELECT 1 FROM POTONGAN WHERE Id_Potongan = 1);



-- Langkah 2: Update data yang NULL dengan nilai default
UPDATE DETAIL_GAJI SET Id_Tunjangan = 1 WHERE Id_Tunjangan IS NULL;
UPDATE DETAIL_GAJI SET Id_Potongan = 1 WHERE Id_Potongan IS NULL;


-- Langkah 3: Alter table untuk mengubah kolom menjadi NOT NULL dengan default value
ALTER TABLE DETAIL_GAJI 
    MODIFY COLUMN Id_Tunjangan INT NOT NULL DEFAULT 1,
    MODIFY COLUMN Id_Potongan INT NOT NULL DEFAULT 1,
    

-- Verifikasi perubahan
DESCRIBE DETAIL_GAJI;

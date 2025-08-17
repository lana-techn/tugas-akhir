-- Script untuk menambahkan data master yang diperlukan sistem penggajian

-- Data Tunjangan
INSERT INTO TUNJANGAN (Nama_Tunjangan, Keterangan) VALUES
('Tunjangan Hari Raya Idul Fitri', 'THR diberikan sebesar 1x gaji pokok'),
('Tunjangan Transport', 'Tunjangan transportasi bulanan'),
('Tunjangan Makan', 'Tunjangan makan harian');

-- Data Potongan  
INSERT INTO POTONGAN (Nama_Potongan, Tarif, Keterangan) VALUES
('BPJS Ketenagakerjaan', 0, 'Potongan BPJS 2% dari gaji pokok'),
('BPJS Kesehatan', 0, 'Potongan BPJS Kesehatan'),
('Potongan Absensi', 0, 'Potongan karena tidak masuk kerja 3% per hari'),
('Potongan Keterlambatan', 50000, 'Potongan untuk keterlambatan'),
('Potongan Lainnya', 0, 'Potongan lain-lain');



-- Verifikasi data telah masuk
SELECT 'Data Tunjangan:' as Info;
SELECT * FROM TUNJANGAN;

SELECT 'Data Potongan:' as Info;
SELECT * FROM POTONGAN;



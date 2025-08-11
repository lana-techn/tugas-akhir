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

-- Data Lembur
INSERT INTO LEMBUR (Nama_Lembur, Upah_Lembur_Per_Jam, Keterangan) VALUES
('Lembur Regular', 20000, 'Lembur hari kerja normal'),
('Lembur Weekend', 25000, 'Lembur hari Sabtu/Minggu'), 
('Lembur Hari Libur', 30000, 'Lembur hari libur nasional'),
('Lembur Malam', 22000, 'Lembur shift malam');

-- Verifikasi data telah masuk
SELECT 'Data Tunjangan:' as Info;
SELECT * FROM TUNJANGAN;

SELECT 'Data Potongan:' as Info;
SELECT * FROM POTONGAN;

SELECT 'Data Lembur:' as Info;
SELECT * FROM LEMBUR;

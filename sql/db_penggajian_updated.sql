-- Updated Database Schema to Match ERD Diagram
-- Includes Jumlah_Lembur column in DETAIL_GAJI table

CREATE TABLE PENGGUNA (
    Id_Pengguna VARCHAR(15) PRIMARY KEY,
    Email VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Level VARCHAR(30) NOT NULL
);

CREATE TABLE JABATAN (
    Id_Jabatan VARCHAR(15) PRIMARY KEY,
    Nama_Jabatan VARCHAR(50) NOT NULL,
    Pendidikan VARCHAR(20)
);

CREATE TABLE KARYAWAN (
    Id_Karyawan VARCHAR(15) PRIMARY KEY,
    Nama_Karyawan VARCHAR(50) NOT NULL,
    Jenis_Kelamin ENUM('Laki-laki', 'Perempuan'),
    Tgl_Lahir DATE,
    Tgl_Awal_Kerja DATE,
    Alamat VARCHAR(255),
    Telepon VARCHAR(15),
    Id_Pengguna VARCHAR(15) UNIQUE,
    Id_Jabatan VARCHAR(15),
    Status VARCHAR(10) DEFAULT 'Aktif',
    FOREIGN KEY (Id_Pengguna) REFERENCES PENGGUNA(Id_Pengguna) ON DELETE SET NULL,
    FOREIGN KEY (Id_Jabatan) REFERENCES JABATAN(Id_Jabatan) ON DELETE SET NULL
);

CREATE TABLE GAJI_POKOK (
    Id_Gapok INT AUTO_INCREMENT PRIMARY KEY,
    Id_Jabatan VARCHAR(15),
    Masa_Kerja INT, -- Dalam tahun
    Nominal DECIMAL(12,2),
    FOREIGN KEY (Id_Jabatan) REFERENCES JABATAN(Id_Jabatan) ON DELETE CASCADE
);

CREATE TABLE PRESENSI (
    Id_Presensi INT AUTO_INCREMENT PRIMARY KEY,
    Id_Karyawan VARCHAR(15),
    Bulan VARCHAR(30),
    Tahun YEAR,
    Hadir INT,
    Sakit INT,
    Izin INT,
    Alpha INT,
    Jam_Lembur INT,
    Uang_Lembur DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan) ON DELETE CASCADE
);

CREATE TABLE TUNJANGAN (
    Id_Tunjangan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Tunjangan VARCHAR(50) DEFAULT 'Tunjangan Hari Raya Idul Fitri',
    Keterangan TEXT
);

CREATE TABLE POTONGAN (
    Id_Potongan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Potongan VARCHAR(50),
    Tarif DECIMAL(12,2),
    Keterangan TEXT
);

CREATE TABLE GAJI (
    Id_Gaji VARCHAR(15) PRIMARY KEY,
    Id_Karyawan VARCHAR(15),
    Tgl_Gaji DATE,
    Total_Tunjangan DECIMAL(12,2),
    Total_Lembur DECIMAL(12,2),
    Total_Potongan DECIMAL(12,2),
    Gaji_Kotor DECIMAL(12,2),
    Gaji_Bersih DECIMAL(12,2),
    Status VARCHAR(20),
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan)
);

CREATE TABLE DETAIL_GAJI (
    Id_Detail_Gaji INT AUTO_INCREMENT PRIMARY KEY,
    Id_Karyawan VARCHAR(15),
    Id_Gaji VARCHAR(15),
    Id_Gapok INT,
    Id_Tunjangan INT,
    Id_Potongan INT,
    Jumlah_Tunjangan DECIMAL(12,2),
    Jumlah_Potongan DECIMAL(12,2),
    Jumlah_Lembur DECIMAL(12,2),
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan),
    FOREIGN KEY (Id_Gaji) REFERENCES GAJI(Id_Gaji),
    FOREIGN KEY (Id_Gapok) REFERENCES GAJI_POKOK(Id_Gapok),
    FOREIGN KEY (Id_Tunjangan) REFERENCES TUNJANGAN(Id_Tunjangan),
    FOREIGN KEY (Id_Potongan) REFERENCES POTONGAN(Id_Potongan)
);

-- Sample Data
INSERT INTO `PENGGUNA` (`Id_Pengguna`, `Email`, `Password`, `Level`) VALUES
('ADM001', 'admin123@gmail.com', 'admin123', 'Admin'),
('PEM001', 'pemilik1@gmail.com', 'pemilik123', 'Pemilik'),
('LIA001', 'lia.manajer@gmail.com', 'karyawan123', 'Karyawan'),
('AND001', 'andi.produksi@gmail.com', 'karyawan123', 'Karyawan'),
('DIR001', 'direktur.utama@gmail.com', 'karyawan123', 'Karyawan');

INSERT INTO `JABATAN` (`Id_Jabatan`, `Nama_Jabatan`, `Pendidikan`) VALUES
('JBT01', 'Direktur', 'S1'),
('JBT02', 'Wakil Direktur', 'S1'),
('JBT03', 'Manajer Umum', 'S1'),
('JBT04', 'Manajer Produksi', 'S1'),
('JBT05', 'Karyawan Produksi', 'SMA/SMK'),
('JBT06', 'Karyawan Bagian Umum', 'SMA/SMK');

INSERT INTO `KARYAWAN` (`Id_Karyawan`, `Nama_Karyawan`, `Jenis_Kelamin`, `Tgl_Lahir`, `Tgl_Awal_Kerja`, `Alamat`, `Telepon`, `Id_Pengguna`, `Id_Jabatan`, `Status`) VALUES
('LIA01', 'Lia Manajer', 'Perempuan', '1995-05-10', '2019-04-01', 'Jl. Manajerial No. 1, Yogyakarta', '08123456789', 'LIA001', 'JBT03', 'Aktif'),
('AND01', 'Andi Finishing', 'Laki-laki', '1998-08-17', '2022-05-01', 'Jl. Produksi No. 5, Bantul', '0876543210', 'AND001', 'JBT05', 'Aktif'),
('DIR01', 'Budi Direktur', 'Laki-laki', '1985-01-15', '2015-01-01', 'Jl. Utama No. 1, Sleman', '0811223344', 'DIR001', 'JBT01', 'Aktif');

INSERT INTO `PRESENSI` (`Id_Karyawan`, `Bulan`, `Tahun`, `Hadir`, `Sakit`, `Izin`, `Alpha`, `Jam_Lembur`, `Uang_Lembur`) VALUES
('LIA01', 'April', 2025, 24, 0, 2, 0, 0, 0),
('AND01', 'Mei', 2025, 26, 0, 0, 1, 10, 200000),
('DIR01', 'Mei', 2025, 27, 0, 0, 0, 0, 0);

INSERT INTO `GAJI_POKOK` (`Id_Jabatan`, `Masa_Kerja`, `Nominal`) VALUES
('JBT01', 0, 5000000), ('JBT01', 2, 5200000), ('JBT01', 5, 5400000), ('JBT01', 8, 5600000),
('JBT02', 0, 4000000), ('JBT02', 2, 4200000), ('JBT02', 5, 4400000), ('JBT02', 8, 4600000),
('JBT03', 0, 3500000), ('JBT03', 2, 3700000), ('JBT03', 5, 3900000), ('JBT03', 8, 4100000),
('JBT04', 0, 2500000), ('JBT04', 2, 2700000), ('JBT04', 5, 2900000), ('JBT04', 8, 3100000),
('JBT05', 0, 2300000), ('JBT05', 2, 2500000), ('JBT05', 5, 2700000), ('JBT05', 8, 2900000),
('JBT06', 0, 1000000), ('JBT06', 2, 1200000), ('JBT06', 5, 1400000), ('JBT06', 8, 1600000);

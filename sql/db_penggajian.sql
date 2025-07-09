CREATE TABLE PENGGUNA (
    Id_Pengguna VARCHAR(15) PRIMARY KEY,
    Email VARCHAR(20),
    Password VARCHAR(255),
    Level VARCHAR(30)
);

CREATE TABLE JABATAN (
    Id_Jabatan VARCHAR(15) PRIMARY KEY,
    Nama_Jabatan VARCHAR(20),
    Pendidikan VARCHAR(20)
);

CREATE TABLE KARYAWAN (
    Id_Karyawan VARCHAR(15) PRIMARY KEY,
    Nama_Karyawan VARCHAR(30),
    Jenis_Kelamin ENUM(\'Laki-laki\', \'Perempuan\'),
    Tgl_Lahir DATE,
    Tgl_Awal_Kerja DATE,
    Alamat VARCHAR(30),
    Telepon VARCHAR(13),
    Id_Pengguna VARCHAR(15),
    Id_Jabatan VARCHAR(15),
    Status TINYINT(1),
    FOREIGN KEY (Id_Pengguna) REFERENCES PENGGUNA(Id_Pengguna),
    FOREIGN KEY (Id_Jabatan) REFERENCES JABATAN(Id_Jabatan)
);

CREATE TABLE GAJI_POKOK (
    Id_Gapok VARCHAR(15) PRIMARY KEY,
    Id_Jabatan VARCHAR(15),
    Masa_Kerja INT,
    Nominal DECIMAL(12,2),
    FOREIGN KEY (Id_Jabatan) REFERENCES JABATAN(Id_Jabatan)
);

CREATE TABLE PRESENSI (
    Id_Presensi VARCHAR(15) PRIMARY KEY,
    Id_Karyawan VARCHAR(15),
    Bulan VARCHAR(30),
    Tahun YEAR,
    Hadir INT,
    Sakit INT,
    Izin INT,
    Alpha INT,
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan)
);

CREATE TABLE TUNJANGAN (
    Id_Tunjangan VARCHAR(15) PRIMARY KEY,
    Nama_Tunjangan VARCHAR(30),
    Jumlah_Tunjangan DECIMAL(12,2),
    Keterangan TEXT
);

CREATE TABLE LEMBUR (
    Id_Lembur VARCHAR(15) PRIMARY KEY,
    Lama_Lembur INT,
    Upah_Lembur DECIMAL(12,2),
    Keterangan TEXT
);

CREATE TABLE POTONGAN (
    Id_Potongan VARCHAR(15) PRIMARY KEY,
    Nama_Potongan VARCHAR(30),
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
    Status TINYINT(1),
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan)
);

CREATE TABLE DETAIL_GAJI (
    Id_Detail_Gaji INT AUTO_INCREMENT PRIMARY KEY,
    Id_Gaji VARCHAR(15),
    Id_Karyawan VARCHAR(15),
    Id_Gapok VARCHAR(15),
    Id_Tunjangan VARCHAR(15),
    Id_Lembur VARCHAR(15),
    Id_Potongan VARCHAR(15),
    Nominal_Gapok DECIMAL(12,2),
    Jumlah_Tunjangan DECIMAL(12,2),
    Jumlah_Potongan DECIMAL(12,2),
    Jumlah_Lembur DECIMAL(12,2),
    FOREIGN KEY (Id_Gaji) REFERENCES GAJI(Id_Gaji),
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan),
    FOREIGN KEY (Id_Gapok) REFERENCES GAJI_POKOK(Id_Gapok),
    FOREIGN KEY (Id_Tunjangan) REFERENCES TUNJANGAN(Id_Tunjangan),
    FOREIGN KEY (Id_Lembur) REFERENCES LEMBUR(Id_Lembur),
    FOREIGN KEY (Id_Potongan) REFERENCES POTONGAN(Id_Potongan)
);

-- Insert demo users
INSERT INTO PENGGUNA (Id_Pengguna, Email, Password, Level) VALUES
('ADM001', 'admin123@gmail.com', 'admin123', 'Admin'),
('PEM001', 'pemilik1@gmail.com', 'pemilik123', 'Pemilik'),
('KAR001', 'karyawan1@gmail.com', 'karyawan123', 'Karyawan');

-- Skema Database Final dengan Konvensi PascalCase

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
    Status VARCHAR(10) DEFAULT 'Aktif', -- Lebih baik menggunakan VARCHAR
    FOREIGN KEY (Id_Pengguna) REFERENCES PENGGUNA(Id_Pengguna) ON DELETE SET NULL,
    FOREIGN KEY (Id_Jabatan) REFERENCES JABATAN(Id_Jabatan) ON DELETE SET NULL
);

CREATE TABLE GAJI_POKOK (
    Id_Gapok INT AUTO_INCREMENT PRIMARY KEY,
    Id_Jabatan VARCHAR(15),
    Masa_Kerja INT,
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
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan) ON DELETE CASCADE
);

CREATE TABLE TUNJANGAN (
    Id_Tunjangan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Tunjangan VARCHAR(50),
    Jumlah_Tunjangan DECIMAL(12,2),
    Keterangan TEXT
);

CREATE TABLE LEMBUR (
    Id_Lembur INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Lembur VARCHAR(50), -- Kolom yang ditambahkan
    Upah_Lembur DECIMAL(12,2),
    Keterangan TEXT
);

CREATE TABLE POTONGAN (
    Id_Potongan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Potongan VARCHAR(50),
    Tarif DECIMAL(12,2),
    Keterangan TEXT
);

-- Tabel GAJI dan DETAIL_GAJI tetap sesuai permintaan Anda
CREATE TABLE GAJI (
    Id_Gaji VARCHAR(15) PRIMARY KEY,
    Id_Karyawan VARCHAR(15),
    Tgl_Gaji DATE,
    Total_Tunjangan DECIMAL(12,2),
    Total_Lembur DECIMAL(12,2),
    Total_Potongan DECIMAL(12,2),
    Gaji_Kotor DECIMAL(12,2),
    Gaji_Bersih DECIMAL(12,2),
    Status VARCHAR(20), -- Mengganti TINYINT
    FOREIGN KEY (Id_Karyawan) REFERENCES KARYAWAN(Id_Karyawan)
);

CREATE TABLE DETAIL_GAJI (
    Id_Detail_Gaji INT AUTO_INCREMENT PRIMARY KEY,
    Id_Gaji VARCHAR(15),
    Id_Karyawan VARCHAR(15),
    Id_Gapok INT,
    Id_Tunjangan INT,
    Id_Lembur INT,
    Id_Potongan INT,
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

-- Pastikan password di-hash menggunakan password_hash() di PHP
INSERT INTO PENGGUNA (Id_Pengguna, Email, Password, Level) VALUES
('a01', 'admin@gmail.com', '$2y$10$Y.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.U', 'Admin'),
('p01', 'pemilik@gmail.com', '$2y$10$Y.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.U', 'Pemilik'),
('k01', 'karyawan@gmail.com', '$2y$10$Y.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.UaY.aJcZ.U', 'Karyawan');

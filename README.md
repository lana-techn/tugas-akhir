# Sistem Penggajian Karyawan

Sistem Penggajian Karyawan adalah aplikasi berbasis web yang dikembangkan menggunakan PHP dan MySQL untuk mengelola data karyawan, jabatan, presensi, lembur, potongan, dan proses penggajian .

## Fitur Utama

*   **Manajemen Pengguna**: Admin dapat mengelola akun pengguna dengan berbagai level akses (Admin, Pemilik, Karyawan).
*   **Manajemen Karyawan**: Mengelola data pribadi dan kepegawaian karyawan.
*   **Manajemen Jabatan**: Mengelola daftar jabatan dan pendidikan minimal yang dibutuhkan.
*   **Manajemen Presensi**: Mencatat dan mengelola data kehadiran karyawan (hadir, sakit, izin, alpha).
*   **Manajemen Lembur**: Mengelola data upah lembur.
*   **Manajemen Potongan**: Mengelola berbagai jenis potongan gaji.
*   **Proses Penggajian Otomatis**: Menghitung gaji pokok, tunjangan, lembur, dan potongan untuk menghasilkan gaji bersih.
*   **Laporan Penggajian**: Pemilik dapat melihat laporan penggajian dan mencetak slip gaji (PDF).
*   **Akses Berbasis Peran**: Tiga level pengguna dengan hak akses yang berbeda (Admin, Pemilik, Karyawan).

## Teknologi yang Digunakan

*   **Backend**: PHP
*   **Database**: MySQL
*   **Manajemen Dependensi PHP**: Composer
*   **Frontend**: HTML, CSS (dengan TailwindCSS), JavaScript
*   **Server Web**: Apache / Nginx
*   **Pustaka Tambahan**: `dompdf/dompdf` untuk pembuatan PDF.

## Instalasi dan Setup

Ikuti langkah-langkah di bawah ini untuk menginstal dan menjalankan proyek ini di lingkungan lokal Anda.

### 1. Kloning Repositori

Buka terminal atau command prompt Anda dan jalankan perintah berikut untuk mengkloning repositori:

```bash
git clone https://github.com/lana-techn/tugas-akhir.git
cd tugas-akhir
```

### 2. Konfigurasi Database

Proyek ini menggunakan database MySQL. Anda perlu membuat database dan mengimpor skema yang disediakan.

a. **Buat Database Baru**

Buka alat manajemen database Anda (misalnya phpMyAdmin, MySQL Workbench, atau klien CLI) dan buat database baru dengan nama `db_penggajian`.

```sql
CREATE DATABASE db_penggajian;
```

b. Impor Skema Database melalui phpMyAdmin

    1. Buka browser web Anda dan navigasikan ke phpMyAdmin (biasanya http://localhost/phpmyadmin).
  
    2. Pilih database db_penggajian yang baru saja Anda buat dari daftar di sisi kiri.
  
    3. Klik tab Import di bagian atas.
  
    4. Klik tombol Choose File atau Browse dan pilih file db_penggajian.sql yang terletak di folder sql/ di dalam direktori proyek Anda (tugas-akhir/sql/db_penggajian.sql).
  
    5. Biarkan opsi lainnya pada nilai default.
  
    6. Scroll ke bawah dan klik tombol Go untuk memulai proses impor.

Setelah proses impor selesai, nanti ada pesan sukses, dan semua tabel yang diperlukan bakalan ada di database db_penggajian.



c. **Konfigurasi Koneksi Database**

Edit file `config/koneksi.php` untuk memastikan kredensial database sesuai dengan pengaturan lingkungan lokal Anda. Secara default, pengaturan sudah disiapkan untuk lingkungan XAMPP/LAMPP standar.

```php
<?php
// config/koneksi.php

define('DB_HOST', '127.0.0.1'); // Biasanya 'localhost' atau '127.0.0.1'
define('DB_USER', 'root');     // Username database Anda
define('DB_PASS', '');         // Password database Anda (kosong jika tidak ada)
define('DB_NAME', 'db_penggajian'); // Nama database yang telah Anda buat

// Pengaturan Aplikasi
define('APP_NAME', 'Sistem Penggajian Karyawan');
define('BASE_URL', 'http://localhost/tugas-akhir'); // Sesuaikan dengan URL proyek Anda

// ... pengaturan lainnya
?>
```

**Penting**: Pastikan `BASE_URL` di atas sesuai dengan lokasi proyek Anda di server web lokal. Jika Anda menempatkan folder `tugas-akhir` langsung di `htdocs` (Apache) atau `www` (Nginx), maka `http://localhost/tugas-akhir` sudah benar.

### 3. Instal Dependensi Composer

Proyek ini menggunakan Composer untuk mengelola dependensi PHP, khususnya `dompdf/dompdf` untuk fungsionalitas ekspor PDF. Jika Anda belum memiliki Composer, unduh dan instal dari [situs resmi Composer](https://getcomposer.org/download/).

Setelah Composer terinstal, navigasikan ke direktori proyek (`tugas-akhir`) di terminal Anda dan jalankan perintah berikut:

```bash
composer install
```

Perintah ini akan mengunduh semua dependensi yang diperlukan dan menyimpannya di folder `vendor/`.

### 4. Konfigurasi Server Web

Tempatkan seluruh folder `tugas-akhir` ke dalam direktori root dokumen server web Anda. Lokasi umum meliputi:

*   **Apache (XAMPP/WAMP/LAMPP)**: `C:\xampp\htdocs\` (Windows) atau `/var/www/html/` (Linux)
*   **Nginx**: `/var/www/html/` atau lokasi yang dikonfigurasi di `nginx.conf` Anda.

Pastikan server web Anda (Apache/Nginx) sedang berjalan.

### 5. Akses Aplikasi

Setelah semua langkah di atas selesai, buka browser web Anda dan navigasikan ke URL berikut:

```
http://localhost/tugas-akhir
```

*(Sesuaikan URL jika Anda mengubah `BASE_URL` di `config/koneksi.php` atau menempatkan proyek di subdirektori lain.)*

Anda akan diarahkan ke halaman login. Gunakan kredensial default dari `sql/db_penggajian.sql` untuk login:

| Level     | Email                  | Password   |
| :-------- | :--------------------- | :--------- |
| Admin     | `admin123@gmail.com`   | `admin123` |
| Pemilik   | `pemilik1@gmail.com`   | `pemilik123` |
| Karyawan  | `karyawan1@gmail.com`  | `karyawan123` |


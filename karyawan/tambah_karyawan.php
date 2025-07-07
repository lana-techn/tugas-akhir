<?php
session_start();
require '../config/koneksi.php';

// Batasi akses jika bukan admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = '';

// Ambil data pengguna & jabatan untuk dropdown
$pengguna = $conn->query("SELECT Id_Pengguna, email FROM pengguna");
$jabatan = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = $_POST['nama'];
    $jk         = $_POST['jenis_kelamin'];
    $telepon    = $_POST['telepon'];
    $alamat     = $_POST['alamat'];
    $tgl_lahir  = $_POST['tgl_lahir'];
    $tgl_masuk  = $_POST['tgl_masuk'];
    $akun       = $_POST['akun_pengguna'];
    $jab        = $_POST['jabatan'];
    $status     = $_POST['status'];

    // Generate ID otomatis: KR001, KR002, dst
    $prefix = "KR";
    $cek_id = $conn->query("SELECT id_karyawan FROM karyawan WHERE id_karyawan LIKE '$prefix%' ORDER BY id_karyawan DESC LIMIT 1");

    if ($row = $cek_id->fetch_assoc()) {
        $last_id = intval(substr($row['id_karyawan'], 2)) + 1;
    } else {
        $last_id = 1;
    }

    $id_karyawan = $prefix . str_pad($last_id, 3, '0', STR_PAD_LEFT);

    // Simpan data ke database
    $stmt = $conn->prepare("INSERT INTO karyawan (id_karyawan, nama_karyawan, jenis_kelamin, telepon, alamat, tgl_lahir, tgl_awal_kerja, id_pengguna, id_jabatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $id_karyawan, $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $akun, $jab, $status);

    if ($stmt->execute()) {
        $pesan = "✅ Data karyawan berhasil ditambahkan. ID: $id_karyawan";
    } else {
        $pesan = "❌ Gagal menambahkan karyawan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Karyawan</title>
    <link rel="stylesheet" href="../public/css/tambah_karyawan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>ADMIN</h3>
        </div>
        <ul class="nav">
            <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li class="has-submenu">
                <a href="#"><i class="fas fa-folder"></i> <span>Data Master</span> <i class="fas fa-chevron-down"></i></a>
                <ul class="submenu active">
                    <li><a href="tambah_pengguna.php">Pengguna</a></li>
                    <li><a href="tambah_jabatan.php">Jabatan</a></li>
                    <li><a href="tambah_karyawan.php" class="active">Karyawan</a></li>
                    <li><a href="tambah_presensi.php">Presensi</a></li>
                    <li><a href="tambah_gapok.php">Gaji Pokok</a></li>
                    <li><a href="tambah_tunjangan.php">Tunjangan</a></li>
                     <li><a href="tambah_lembur.php">Lembur</a></li>
                    <li><a href="tambah_potongan.php">Potongan</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-money-bill"></i> <span>Penggajian</span> <i class="fas fa-chevron-down"></i></a>
                    <ul class="submenu active">
                        <li><a href="pengajuan_gaji.php" class="active">Pengajuan Gaji</a></li>
                        <li><a href="#">Daftar Gaji</a></li>
                        <li><a href="#">Slip Gaji</a></li>
                    </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-file-alt"></i> <span>Laporan</span> <i class="fas fa-chevron-down"></i></a>
                <ul class="submenu">
                    <li><a href="#">Gaji Per Bulan</a></li>
                    <li><a href="#">Gaji Per Jabatan</a></li>
                </ul>
            </li>

            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
        </div>

        <div class="dashboard-header">
            <h2>Tambah Karyawan</h2>
            <a href="daftar_karyawan.php" class="btn-daftar">Daftar</a>
        </div>

        <?php if ($pesan): ?>
            <div class="notif"> <?= $pesan ?> </div>
        <?php endif; ?>

        <form method="POST" class="form-box">
            <h2>KARYAWAN</h2>
            <div class="form-grid">
                <div>
                    <label>Nama Karyawan:</label>
                    <input type="text" name="nama" required>

                    <label>Jenis Kelamin:</label>
                    <select name="jenis_kelamin" required>
                        <option value="">-Pilih Jenis Kelamin-</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>

                    <label>Nomor Telepon:</label>
                    <input type="text" name="telepon" required>

                    <label>Alamat:</label>
                    <textarea name="alamat" rows="4" required></textarea>
                </div>

                <div>
                    <label>Tanggal Lahir:</label>
                    <input type="date" name="tgl_lahir" required>

                    <label>Tanggal Awal Bekerja:</label>
                    <input type="date" name="tgl_masuk" required>

                    <label>Akun Pengguna:</label>
                    <select name="akun_pengguna" required>
                        <option value="">-Pilih Akun Pengguna-</option>
                        <?php while ($row = $pengguna->fetch_assoc()): ?>
                            <option value="<?= $row['Id_Pengguna'] ?>"><?= $row['email'] ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Jabatan:</label>
                    <select name="jabatan" required>
                        <option value="">-Pilih Jabatan-</option>
                        <?php while ($row = $jabatan->fetch_assoc()): ?>
                            <option value="<?= $row['id_jabatan'] ?>"><?= $row['nama_jabatan'] ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Status:</label>
                    <select name="status" required>
                        <option value="">-Pilih Status-</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="form-buttons">
                <a href="index.php" class="btn-batal">Batal</a>
                <button type="submit" class="btn-simpan">Simpan</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll(".has-submenu > a").forEach(function(menu) {
            menu.addEventListener("click", function(e) {
                e.preventDefault();
                const submenu = this.parentElement.querySelector(".submenu");
                submenu.classList.toggle("active");
            });
        });
    </script>
</body>
</html>

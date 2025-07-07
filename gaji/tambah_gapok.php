<?php
session_start();
require '../config/koneksi.php';

// Batasi akses jika bukan admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = '';

// Ambil data jabatan untuk dropdown
$jabatan = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jabatan = $_POST['id_jabatan'];
    $masa_kerja = $_POST['masa_kerja'];
    $nominal    = $_POST['nominal'];

    // Generate ID otomatis: GP001, GP002, dst.
    $cek = $conn->query("SELECT id_gapok FROM gaji_pokok WHERE id_gapok LIKE 'GP%' ORDER BY id_gapok DESC LIMIT 1");
    if ($row = $cek->fetch_assoc()) {
        $last = intval(substr($row['id_gapok'], 2)) + 1;
    } else {
        $last = 1;
    }
    $id_gapok = "GP" . str_pad($last, 3, '0', STR_PAD_LEFT);

    if (!empty($id_jabatan) && !empty($masa_kerja) && !empty($nominal)) {
        $stmt = $conn->prepare("INSERT INTO gaji_pokok (id_gapok, id_jabatan, masa_kerja, nominal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $id_gapok, $id_jabatan, $masa_kerja, $nominal);

        if ($stmt->execute()) {
            $pesan = "✅ Gaji Pokok berhasil ditambahkan.";
        } else {
            $pesan = "❌ Gagal menambahkan data.";
        }
    } else {
        $pesan = "❌ Semua field wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Gaji Pokok</title>
    <link rel="stylesheet" href="../public/css/tambah_gapok.css">
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
                <li><a href="tambah_karyawan.php">Karyawan</a></li>
                <li><a href="tambah_presensi.php">Presensi</a></li>
                <li><a href="tambah_gapok.php" class="active">Gaji Pokok</a></li>
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
        <h2>Tambah Gaji Pokok</h2>
        <a href="daftar_gapok.php" class="btn-daftar">Daftar</a>
    </div>

    <?php if ($pesan): ?>
        <div class="notif"><?= $pesan ?></div>
    <?php endif; ?>

    <form method="POST" class="form-box">
        <h2>GAJI POKOK</h2>
        <label>Nama Jabatan:</label>
        <select name="id_jabatan" required>
            <option value="">-Pilih Jabatan-</option>
            <?php while ($row = $jabatan->fetch_assoc()): ?>
                <option value="<?= $row['id_jabatan'] ?>"><?= $row['nama_jabatan'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Masa Kerja:</label>
        <input type="text" name="masa_kerja" required>

        <label>Nominal:</label>
        <input type="number" name="nominal" required>

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

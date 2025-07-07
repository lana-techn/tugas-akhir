<?php
session_start();
require '../config/koneksi.php';

// Cek apakah pengguna login dan levelnya admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lembur = trim($_POST['nama_lembur']);
    $lama_lembur = trim($_POST['lama_lembur']);
    $upah_lembur = trim($_POST['upah_lembur']);
    $keterangan = trim($_POST['keterangan']);

    // Generate ID otomatis (L001, L002, dst)
    $cek_id = $conn->query("SELECT id_lembur FROM lembur ORDER BY id_lembur DESC LIMIT 1");
    if ($row = $cek_id->fetch_assoc()) {
        $last = intval(substr($row['id_lembur'], 1)) + 1;
    } else {
        $last = 1;
    }
    $id_lembur = 'L' . str_pad($last, 3, '0', STR_PAD_LEFT);

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO lembur (id_lembur, nama_lembur, lama_lembur, upah_lembur, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $id_lembur, $nama_lembur, $lama_lembur, $upah_lembur, $keterangan);

    if ($stmt->execute()) {
        $pesan = "✅ Data lembur berhasil ditambahkan.";
    } else {
        $pesan = "❌ Gagal menambahkan data lembur.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Lembur</title>
    <link rel="stylesheet" href="../public/css/tambah_lembur.css">
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
                <li><a href="tambah_gapok.php">Gaji Pokok</a></li>
                <li><a href="tambah_tunjangan.php">Tunjangan</a></li>
                <li><a href="tambah_lembur.php" class="active">Lembur</a></li>
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
        <h2>Tambah Lembur</h2>
        <a href="daftar_lembur.php" class="btn-daftar">Daftar</a>
    </div>

    <?php if ($pesan): ?>
        <div class="notif"> <?= $pesan ?> </div>
    <?php endif; ?>

    <form method="POST" class="form-box">
        <h2>LEMBUR</h2>
        <label>Nama Lembur:</label>
        <input type="text" name="nama_lembur" required>

        <label>Lama Lembur (/Jam):</label>
        <input type="number" name="lama_lembur" required>

        <label>Upah Lembur:</label>
        <input type="number" name="upah_lembur" required>

        <label>Keterangan:</label>
        <textarea name="keterangan" rows="4"></textarea>

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

<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];

// Ambil daftar karyawan aktif
$query = "SELECT id_karyawan, nama_karyawan FROM karyawan WHERE status = 'Aktif'";
$result = mysqli_query($conn, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = $_POST['id_karyawan'] ?? '';
    $tanggal = $_POST['tgl_gaji'] ?? '';

    if ($id_karyawan && $tanggal) {
        // Buat ID Gaji otomatis
        $id_gaji = 'GJ' . date('YmdHis');

        // Simpan ke database
        $stmt = $conn->prepare("INSERT INTO gaji (id_gaji, id_karyawan, tgl_gaji, status) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $id_gaji, $id_karyawan, $tanggal);
        $stmt->execute();

        // Redirect ke detail_pengajuan.php sambil kirim id_gaji lewat GET
        header("Location: detail_pengajuan.php?id_gaji=" . $id_gaji);
        exit;
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Gaji</title>
    <link rel="stylesheet" href="../public/css/pengajuan_gaji.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h3>ADMIN</h3></div>
    <ul class="nav">
        <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>

        <li class="has-submenu">
            <a href="#"><i class="fas fa-folder"></i> <span>Data Master</span> <i class="fas fa-chevron-down"></i></a>
            <ul class="submenu">
                <li><a href="daftar_pengguna.php">Pengguna</a></li>
                <li><a href="daftar_jabatan.php">Jabatan</a></li>
                <li><a href="daftar_karyawan.php">Karyawan</a></li>
                <li><a href="daftar_presensi.php">Presensi</a></li>
                <li><a href="daftar_gapok.php">Gaji Pokok</a></li>
                <li><a href="daftar_tunjangan.php">Tunjangan</a></li>
                <li><a href="daftar_lembur.php">Lembur</a></li>
                <li><a href="daftar_potongan.php">Potongan</a></li>
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
        <span><?= $level ?>@gmail.com</span>
    </div>

    <div class="dashboard-header">
        <h2>Pengajuan Gaji</h2>
    </div>

    <div class="form-box">
        <form method="POST">
            <label for="id_karyawan">Nama Karyawan</label>
            <select name="id_karyawan" required>
                <option value="">- Pilih Karyawan -</option>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <option value="<?= $row['id_karyawan'] ?>"><?= htmlspecialchars($row['nama_karyawan']) ?></option>
                <?php endwhile ?>
            </select>

            <label for="tgl_gaji">Tanggal Gaji</label>
            <input type="date" name="tgl_gaji" required>

            <div class="form-buttons">
                <a href="index.php" class="btn-batal">Batal</a>
                <button type="submit" class="btn-simpan">Selanjutnya</button>
            </div>

            <?php if (isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        </form>
    </div>
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

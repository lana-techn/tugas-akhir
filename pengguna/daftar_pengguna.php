<?php
session_start();
require '../config/koneksi.php';

// Cek login dan level admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$query = "SELECT * FROM pengguna";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengguna</title>
    <link rel="stylesheet" href="../public/css/daftar_pengguna.css">
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
                <li><a href="daftar_pengguna.php" class="active">Pengguna</a></li>
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
        <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
    </div>

    <div class="content">
        <div class="header">
            <h2>DAFTAR PENGGUNA</h2>
            <a href="tambah_pengguna.php" class="btn-tambah">Tambah</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>EMAIL</th>
                    <th>LEVEL</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) :
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['level']) ?></td>
                        <td>
                            <a href="edit_pengguna.php?id=<?= $row['id_pengguna'] ?>" class="btn-edit">Edit</a>
                            <a href="hapus_pengguna.php?id=<?= $row['id_pengguna'] ?>" class="btn-hapus" onclick="return confirm('Anda yakin ingin menghapusnya ?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile ?>
            </tbody>
        </table>
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

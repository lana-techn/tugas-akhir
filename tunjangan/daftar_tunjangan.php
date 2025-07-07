<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$query = "SELECT * FROM tunjangan";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tunjangan</title>
    <link rel="stylesheet" href="../public/css/daftar_tunjangan.css">
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
                <li><a href="daftar_pengguna.php">Pengguna</a></li>
                <li><a href="daftar_jabatan.php">Jabatan</a></li>
                <li><a href="daftar_karyawan.php">Karyawan</a></li>
                <li><a href="daftar_presensi.php">Presensi</a></li>
                <li><a href="daftar_gapok.php">Gaji Pokok</a></li>
                <li><a href="daftar_tunjangan.php" class="active">Tunjangan</a></li>
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
    <div class="topbar"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>

    <div class="content">
        <div class="header">
            <h2>DAFTAR TUNJANGAN</h2>
            <a href="tambah_tunjangan.php" class="btn-tambah">Tambah</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA TUNJANGAN</th>
                    <th>JUMLAH TUNJANGAN</th>
                    <th>KETERANGAN</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_tunjangan']) ?></td>
                    <td><?= htmlspecialchars($row['jumlah_tunjangan']) ?></td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td>
                        <a href="edit_tunjangan.php?id=<?= $row['id_tunjangan'] ?>" class="btn-edit">Edit</a>
                        <a href="hapus_tunjangan.php?id=<?= $row['id_tunjangan'] ?>" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')" 
                           class="btn-hapus">Hapus</a>
                    </td>
                </tr>
                <?php endwhile ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

<?php 
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil filter
$jabatan_filter = $_GET['jabatan'] ?? '';
$masa_kerja_filter = $_GET['tahun'] ?? '';

// Siapkan WHERE clause jika ada filter
$where = [];
if ($jabatan_filter !== '') {
    $where[] = "gapok.id_jabatan = '" . mysqli_real_escape_string($conn, $jabatan_filter) . "'";
}
if ($masa_kerja_filter !== '') {
    $where[] = "gapok.masa_kerja = '" . mysqli_real_escape_string($conn, $masa_kerja_filter) . "'";
}

$filter_sql = '';
if (!empty($where)) {
    $filter_sql = 'WHERE ' . implode(' AND ', $where);
}

// Query utama gabung dengan jabatan
$query = "SELECT gapok.*, jabatan.nama_jabatan 
          FROM gaji_pokok AS gapok 
          JOIN jabatan ON gapok.id_jabatan = jabatan.id_jabatan 
          $filter_sql 
          ORDER BY jabatan.nama_jabatan ASC";

$result = mysqli_query($conn, $query);
$jabatanList = mysqli_query($conn, "SELECT * FROM jabatan");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Gaji Pokok</title>
    <link rel="stylesheet" href="../public/css/daftar_gapok.css">
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
                <li><a href="daftar_gapok.php" class="active">Gaji Pokok</a></li>
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
    <div class="topbar">Selamat datang, <?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>

    <div class="content">
        <div class="header">
            <h2>DAFTAR GAJI POKOK</h2>
            <a href="tambah_gapok.php" class="btn-tambah">Tambah</a>
        </div>

        <form method="GET" class="filter-form">
            <select name="jabatan">
                <option value="">-Pilih Jabatan-</option>
                <?php while ($j = mysqli_fetch_assoc($jabatanList)) : ?>
                    <option value="<?= $j['id_jabatan'] ?>" <?= $jabatan_filter == $j['id_jabatan'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['nama_jabatan']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="tahun" placeholder="Masukkan Masa Kerja (Tahun)" value="<?= htmlspecialchars($masa_kerja_filter) ?>">
            <button type="submit" class="btn-filter">Tampilkan</button>
            <a href="daftar_gapok.php" class="btn-reset">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA JABATAN</th>
                    <th>MASA KERJA</th>
                    <th>NOMINAL</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                            <td><?= htmlspecialchars($row['masa_kerja']) ?> Tahun</td>
                            <td>Rp<?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <td>
                                <a href="edit_gapok.php?id=<?= $row['id_gapok'] ?>" class="btn-edit">Edit</a>
                                <a href="hapus_gapok.php?id=<?= $row['id_gapok'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus?')" class="btn-hapus">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

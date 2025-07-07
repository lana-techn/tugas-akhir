<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil filter
$filterNama = $_GET['nama'] ?? '';
$filterTahun = $_GET['tahun'] ?? '';

// Query join + filter
$query = "SELECT presensi.*, karyawan.nama_karyawan 
          FROM presensi 
          JOIN karyawan ON presensi.id_karyawan = karyawan.id_karyawan
          WHERE 1";

if (!empty($filterNama)) {
    $query .= " AND karyawan.nama_karyawan LIKE '%$filterNama%'";
}
if (!empty($filterTahun)) {
    $query .= " AND presensi.tahun = '$filterTahun'";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Presensi</title>
    <link rel="stylesheet" href="../public/css/daftar_presensi.css">
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
                <li><a href="daftar_presensi.php" class="active">Presensi</a></li>
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
    <div class="topbar"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>

    <div class="content">
        <div class="header">
            <h2>DAFTAR PRESENSI</h2>
            <a href="tambah_presensi.php" class="btn-tambah">Tambah</a>
        </div>

        <form method="GET" class="filter-form">
            <select name="nama">
                <option value="">-- Pilih Nama Karyawan --</option>
                <?php
                $karyawanList = mysqli_query($conn, "SELECT DISTINCT nama_karyawan FROM karyawan ORDER BY nama_karyawan");
                while ($k = mysqli_fetch_assoc($karyawanList)) {
                    $selected = ($k['nama_karyawan'] == $filterNama) ? 'selected' : '';
                    echo "<option value=\"{$k['nama_karyawan']}\" $selected>{$k['nama_karyawan']}</option>";
                }
                ?>
            </select>

            <input type="number" name="tahun" placeholder="Tahun" value="<?= htmlspecialchars($filterTahun) ?>">

            <button type="submit" class="btn-filter">Tampilkan</button>
            <a href="daftar_presensi.php" class="btn-reset">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA KARYAWAN</th>
                    <th>BULAN</th>
                    <th>TAHUN</th>
                    <th>H</th>
                    <th>S</th>
                    <th>I</th>
                    <th>A</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                    <td><?= htmlspecialchars($row['bulan']) ?></td>
                    <td><?= htmlspecialchars($row['tahun']) ?></td>
                    <td><?= $row['hadir'] ?></td>
                    <td><?= $row['sakit'] ?></td>
                    <td><?= $row['izin'] ?></td>
                    <td><?= $row['alpha'] ?></td>
                    <td>
                        <a href="edit_presensi.php?id=<?= $row['id_presensi'] ?>" class="btn-edit">Edit</a>
                        <a href="hapus_presensi.php?id=<?= $row['id_presensi'] ?>" onclick="return confirm('Apakah anda yakin ingin menghapus?')" class="btn-hapus">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

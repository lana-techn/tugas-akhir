<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "ID tidak valid.";
    exit;
}

$result = $conn->query("SELECT * FROM karyawan WHERE id_karyawan = '$id'");
$karyawan = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_karyawan'];
    $jabatan = $_POST['id_jabatan'];
    $jk = $_POST['jenis_kelamin'];
    $tgl = $_POST['tgl_awal_kerja'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE karyawan SET nama_karyawan=?, id_jabatan=?, jenis_kelamin=?, tgl_awal_kerja=?, telepon=?, alamat=?, status=? WHERE id_karyawan=?");
    $stmt->bind_param("ssssssss", $nama, $jabatan, $jk, $tgl, $telepon, $alamat, $status, $id);
    $stmt->execute();
    
    header("Location: daftar_karyawan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Karyawan</title>
    <link rel="stylesheet" href="../public/css/form_karyawan.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Karyawan</h2>
    <form method="POST">
        <label>Nama Karyawan:</label>
        <input type="text" name="nama_karyawan" value="<?= htmlspecialchars($karyawan['nama_karyawan']) ?>" required>

        <label>Jabatan:</label>
        <input type="text" name="id_jabatan" value="<?= htmlspecialchars($karyawan['id_jabatan']) ?>" required>

        <label>Jenis Kelamin:</label>
        <select name="jenis_kelamin">
            <option value="Laki-laki" <?= $karyawan['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= $karyawan['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
        </select>

        <label>Tanggal Awal Kerja:</label>
        <input type="date" name="tgl_awal_kerja" value="<?= htmlspecialchars($karyawan['tgl_awal_kerja']) ?>" required>

        <label>No Telepon:</label>
        <input type="text" name="telepon" value="<?= htmlspecialchars($karyawan['telepon']) ?>" required>

        <label>Alamat:</label>
        <textarea name="alamat" required><?= htmlspecialchars($karyawan['alamat']) ?></textarea>

        <label>Status:</label>
        <select name="status">
            <option value="Aktif" <?= $karyawan['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="Tidak Aktif" <?= $karyawan['status'] === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
        </select>

        <button type="submit">Simpan</button>
        <a href="daftar_karyawan.php">Batal</a>
    </form>
</div>
</body>
</html>

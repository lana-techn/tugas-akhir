<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "ID tidak valid.";
    exit;
}

// Ambil data tunjangan dari database
$result = $conn->query("SELECT * FROM tunjangan WHERE id_tunjangan = '$id'");
$tunjangan = $result->fetch_assoc();

if (!$tunjangan) {
    echo "Data tidak ditemukan.";
    exit;
}

// Proses update saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_tunjangan'];
    $jumlah = $_POST['jumlah_tunjangan'];
    $keterangan = $_POST['keterangan'];

    $stmt = $conn->prepare("UPDATE tunjangan SET nama_tunjangan=?, jumlah_tunjangan=?, keterangan=? WHERE id_tunjangan=?");
    $stmt->bind_param("siss", $nama, $jumlah, $keterangan, $id);
    $stmt->execute();

    header("Location: daftar_tunjangan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Tunjangan</title>
    <link rel="stylesheet" href="../public/css/form_tunjangan.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Data Tunjangan</h2>
    <form method="POST">
        <label>Nama Tunjangan:</label>
        <input type="text" name="nama_tunjangan" value="<?= htmlspecialchars($tunjangan['nama_tunjangan']) ?>" required>

        <label>Jumlah Tunjangan:</label>
        <input type="number" name="jumlah" value="<?= htmlspecialchars($tunjangan['jumlah_tunjangan']) ?>" required>

        <label>Keterangan:</label>
        <textarea name="keterangan"><?= htmlspecialchars($tunjangan['keterangan']) ?></textarea>

        <button type="submit">Simpan</button>
        <a href="daftar_tunjangan.php">Batal</a>
    </form>
</div>
</body>
</html>

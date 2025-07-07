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

$result = $conn->query("SELECT * FROM lembur WHERE id_lembur = '$id'");
$lembur = $result->fetch_assoc();

if (!$lembur) {
    echo "Data tidak ditemukan.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_lembur'];
    $lama = $_POST['lama_jam'];
    $upah = $_POST['upah'];
    $keterangan = $_POST['keterangan'];

    $stmt = $conn->prepare("UPDATE lembur SET nama_lembur=?, lama_jam=?, upah=?, keterangan=? WHERE id_lembur=?");
    $stmt->bind_param("siiss", $nama, $lama, $upah, $keterangan, $id);
    $stmt->execute();

    header("Location: daftar_lembur.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Lembur</title>
    <link rel="stylesheet" href="../public/css/form_lembur.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Data Lembur</h2>
    <form method="POST">
        <label>Nama Lembur:</label>
        <input type="text" name="nama_lembur" value="<?= htmlspecialchars($lembur['nama_lembur']) ?>" required>

        <label>Lama Lembur / Jam:</label>
        <input type="number" name="lama_jam" value="<?= htmlspecialchars($lembur['lama_lembur']) ?>" required>

        <label>Upah Lembur:</label>
        <input type="number" name="upah" value="<?= htmlspecialchars($lembur['upah_lembur']) ?>" required>

        <label>Keterangan:</label>
        <textarea name="keterangan"><?= htmlspecialchars($lembur['keterangan']) ?></textarea>

        <button type="submit">Simpan</button>
        <a href="daftar_lembur.php">Batal</a>
    </form>
</div>
</body>
</html>

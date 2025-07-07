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

$result = $conn->query("SELECT * FROM potongan WHERE id_potongan = '$id'");
$potongan = $result->fetch_assoc();

if (!$potongan) {
    echo "Data tidak ditemukan.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_potongan'];
    $tarif = $_POST['tarif'];
    $keterangan = $_POST['keterangan'];

    $stmt = $conn->prepare("UPDATE potongan SET nama_potongan=?, tarif=?, keterangan=? WHERE id_potongan=?");
    $stmt->bind_param("sis", $nama, $tarif, $keterangan, $id);
    $stmt->execute();

    header("Location: daftar_potongan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Potongan</title>
    <link rel="stylesheet" href="../public/css/form_potongan.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Data Potongan</h2>
    <form method="POST">
        <label>Nama Potongan:</label>
        <input type="text" name="nama_potongan" value="<?= htmlspecialchars($potongan['nama_potongan']) ?>" required>

        <label>Tarif:</label>
        <input type="number" name="tarif" value="<?= htmlspecialchars($potongan['tarif']) ?>" required>

        <label>Keterangan:</label>
        <textarea name="keterangan"><?= htmlspecialchars($potongan['keterangan']) ?></textarea>

        <button type="submit">Simpan</button>
        <a href="daftar_potongan.php">Batal</a>
    </form>
</div>
</body>
</html>

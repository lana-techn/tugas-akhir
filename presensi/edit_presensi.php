<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "ID tidak valid."; exit;
}

$query = $conn->query("SELECT * FROM presensi WHERE id_presensi = '$id'");
$data = $query->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hadir = $_POST['hadir'];
    $sakit = $_POST['sakit'];
    $izin = $_POST['izin'];
    $alpha = $_POST['alpha'];

    $stmt = $conn->prepare("UPDATE presensi SET hadir=?, sakit=?, izin=?, alpha=? WHERE id_presensi=?");
    $stmt->bind_param("iiisi", $hadir, $sakit, $izin, $alpha, $id);
    $stmt->execute();
    header("Location: daftar_presensi.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Presensi</title>
    <link rel="stylesheet" href="../public/css/form_presensi.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Presensi</h2>
    <form method="post">
        <label>Hadir:</label>
        <input type="number" name="hadir" value="<?= $data['hadir'] ?>" required>

        <label>Sakit:</label>
        <input type="number" name="sakit" value="<?= $data['sakit'] ?>" required>

        <label>Izin:</label>
        <input type="number" name="izin" value="<?= $data['izin'] ?>" required>

        <label>Alpha:</label>
        <input type="number" name="alpha" value="<?= $data['alpha'] ?>" required>

        <button type="submit">Simpan</button>
        <a href="daftar_presensi.php">Batal</a>
    </form>
</div>
</body>
</html>

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

// Ambil data berdasarkan ID
$query = "SELECT * FROM gaji_pokok WHERE id_gapok = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jabatan = $_POST['id_jabatan'];
    $masa_kerja = $_POST['masa_kerja'];
    $nominal = $_POST['nominal'];

    $update = $conn->prepare("UPDATE gaji_pokok SET id_jabatan=?, masa_kerja=?, nominal=? WHERE id_gapok=?");
    $update->bind_param("sssi", $id_jabatan, $masa_kerja, $nominal, $id);
    $update->execute();

    header("Location: daftar_gapok.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Gaji Pokok</title>
    <link rel="stylesheet" href="../public/css/form_gapok.css">
</head>
<body>
    <div class="main-content">
        <h2>Edit Gaji Pokok</h2>
        <form method="POST">
            <label>Jabatan:</label>
            <input type="text" name="id_jabatan" value="<?= htmlspecialchars($data['id_jabatan']) ?>" required>

            <label>Masa Kerja:</label>
            <input type="text" name="masa_kerja" value="<?= htmlspecialchars($data['masa_kerja']) ?>" required>

            <label>Nominal:</label>
            <input type="number" name="nominal" value="<?= htmlspecialchars($data['nominal']) ?>" required>

            <div class="form-buttons">
                <button type="submit" class="btn-simpan">Simpan</button>
                <a href="daftar_gapok.php" class="btn-batal">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>

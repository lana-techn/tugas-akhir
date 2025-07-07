<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: daftar_jabatan.php");
    exit;
}

$query = $conn->prepare("SELECT * FROM jabatan WHERE id_jabatan = ?");
$query->bind_param("i", $id);
$query->execute();
$data = $query->get_result()->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan!";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_jabatan'];
    $pendidikan = $_POST['pendidikan'];

    $stmt = $conn->prepare("UPDATE jabatan SET nama_jabatan = ?, pendidikan = ? WHERE id_jabatan = ?");
    $stmt->bind_param("ssi", $nama, $pendidikan, $id);
    $stmt->execute();

    header("Location: daftar_jabatan.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Jabatan</title>
    <link rel="stylesheet" href="../public/css/form_jabatan.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Jabatan</h2>
    <form method="POST" class="form-box">

        <label>Nama Jabatan:</label>
        <input type="text" name="nama_jabatan" value="<?= htmlspecialchars($data['nama_jabatan']) ?>" required>

        <label for="pendidikan">Pendidikan:</label>
        <select name="pendidikan" id="pendidikan" required>
            <option value="">-- Pilih Pendidikan --</option>
            <option value="SMA/SMK">SMA/SMK</option>
            <option value="D3">D3</option>
            <option value="D4">D4</option>
            <option value="S1">S1</option>
            <option value="S2">S2</option>
        </select>

        <div class="form-buttons">
            <a href="daftar_jabatan.php" class="btn-batal">Batal</a>
            <button type="submit" class="btn-simpan">Simpan</button>
        </div>
    </form>
</div>
</body>
</html>

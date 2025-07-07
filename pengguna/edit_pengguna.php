<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil ID dari URL
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: daftar_pengguna.php");
    exit;
}

// Ambil data pengguna berdasarkan ID
$query = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan!";
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $level = $_POST['level'];

    $stmt = $conn->prepare("UPDATE pengguna SET email = ?, level = ? WHERE id_pengguna = ?");
    $stmt->bind_param("ssi", $email, $level, $id);

    if ($stmt->execute()) {
        header("Location: daftar_pengguna.php");
        exit;
    } else {
        $pesan = "❌ Gagal memperbarui data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pengguna</title>
    <link rel="stylesheet" href="../public/css/form_pengguna.css">
</head>
<body>
<div class="main-content">
    <h2>Edit Pengguna</h2>

    <?php if ($pesan): ?>
        <div class="notif"><?= $pesan ?></div>
    <?php endif; ?>

    <form method="POST" class="form-box">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" required>

        <label>Level:</label>
        <select name="level" required>
            <option value="admin" <?= $data['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="karyawan" <?= $data['level'] == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
        </select>

        <div class="form-buttons">
            <a href="daftar_pengguna.php" class="btn-batal">Batal</a>
            <button type="submit" class="btn-simpan">Simpan</button>
        </div>
    </form>
</div>
</body>
</html>

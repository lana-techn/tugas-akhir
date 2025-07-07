<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? '';
if ($id) {
    $stmt = $conn->prepare("DELETE FROM presensi WHERE id_presensi = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
}

header("Location: daftar_presensi.php");
exit;
?>

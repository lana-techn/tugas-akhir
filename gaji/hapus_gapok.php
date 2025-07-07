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

$stmt = $conn->prepare("DELETE FROM gapok WHERE id_gapok = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: daftar_gapok.php");
exit;
?>

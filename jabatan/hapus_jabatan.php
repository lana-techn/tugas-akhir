<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if ($id) {
    // Cek apakah masih digunakan di tabel tunjangan
    $cek = $conn->prepare("SELECT COUNT(*) FROM tunjangan WHERE Id_Jabatan = ?");
    $cek->bind_param("s", $id);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        echo "<script>alert('❌ Tidak bisa menghapus. Jabatan masih digunakan di data tunjangan.'); window.location='daftar_jabatan.php';</script>";
        exit;
    }

    // Jika aman, hapus
    $stmt = $conn->prepare("DELETE FROM jabatan WHERE id_jabatan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
}

header("Location: daftar_jabatan.php");
exit;

?>

<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id_gaji = $_GET['id_gaji'] ?? '';

if (!$id_gaji) {
    echo "Data tidak lengkap.";
    exit;
}

// Ambil data dari tabel gaji
$q = $conn->query("SELECT g.*, k.nama_karyawan, k.tgl_awal_kerja, j.nama_jabatan 
                   FROM gaji g
                   JOIN karyawan k ON g.id_karyawan = k.id_karyawan
                   JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                   WHERE g.id_gaji = '$id_gaji'");

$data = $q->fetch_assoc();

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

$id_karyawan = $data['id_karyawan'];
$tanggal_gaji = $data['tgl_gaji'];

// Ambil gaji pokok dari tabel gaji_pokok
$q_gapok = $conn->query("SELECT gapok FROM gaji_pokok WHERE id_karyawan = '$id_karyawan'");
$data_gapok = $q_gapok->fetch_assoc();
$gapok = (int)$data_gapok['gapok'];

// Cek apakah tanggal gaji bertepatan dengan Idul Fitri
$isIdulFitri = (date('m-d', strtotime($tanggal_gaji)) === '04-10');
$tunjangan = $isIdulFitri ? $gapok : 0;

// Hitung lembur
$q2 = $conn->query("SELECT lama_lembur, upah_lembur FROM lembur WHERE id_karyawan = '$id_karyawan'");
$lembur_data = $q2->fetch_assoc();
$lembur_total = isset($lembur_data['lama_lembur']) ? $lembur_data['lama_lembur'] * $lembur_data['upah_lembur'] : 0;

// Potongan BPJS
$potongan_bpjs = 0.02 * $gapok;

// Potongan Absensi
$q3 = $conn->query("SELECT sakit, izin, alpha, tanpa_keterangan FROM presensi WHERE id_karyawan = '$id_karyawan' AND MONTH(tanggal) = MONTH('$tanggal_gaji') AND YEAR(tanggal) = YEAR('$tanggal_gaji')");
$presensi = $q3->fetch_assoc();
$jumlah_absen = ($presensi['sakit'] ?? 0) + ($presensi['izin'] ?? 0) + ($presensi['alpha'] ?? 0) + ($presensi['tanpa_keterangan'] ?? 0);
$potongan_absen = 0.03 * $gapok * $jumlah_absen;

// Total potongan
$total_potongan = $potongan_bpjs + $potongan_absen;

// Gaji kotor & bersih
$gaji_kotor = $gapok + $tunjangan + $lembur_total;
$gaji_bersih = $gaji_kotor - $total_potongan;
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pengajuan Gaji</title>
    <link rel="stylesheet" href="../public/css/detail_pengajuan.css">
</head>
<body>
<div class="main-content">
    <h2>DETAIL PENGAJUAN GAJI</h2>

    <p><strong>Tanggal Gaji:</strong> <?= date('d/m/Y', strtotime($data['tgl_gaji'])) ?></p>
    <p><strong>Nama Karyawan:</strong> <?= htmlspecialchars($data['nama_karyawan']) ?></p>
    <p><strong>Jabatan:</strong> <?= htmlspecialchars($data['nama_jabatan']) ?></p>
    <p><strong>Masa Kerja:</strong> <?= date('Y') - date('Y', strtotime($data['tgl_awal_kerja'])) ?> tahun</p>

    <hr>
    <h3>Gaji</h3>
    <p><strong>Gaji Pokok:</strong> Rp<?= number_format($gapok, 0, ',', '.') ?></p>

    <h3>Tunjangan</h3>
    <p><strong>Tunjangan:</strong> Rp<?= number_format($tunjangan, 0, ',', '.') ?></p>

    <h3>Lembur</h3>
    <p><strong>Lembur:</strong> Rp<?= number_format($lembur_total, 0, ',', '.') ?></p>

    <h3>Potongan</h3>
    <p><strong>Potongan BPJS:</strong> Rp<?= number_format($potongan_bpjs, 0, ',', '.') ?></p>
    <p><strong>Potongan Absensi:</strong> Rp<?= number_format($potongan_absen, 0, ',', '.') ?></p>
    <p><strong>Total Potongan:</strong> Rp<?= number_format($total_potongan, 0, ',', '.') ?></p>

    <hr>
    <p><strong>Gaji Kotor:</strong> Rp<?= number_format($gaji_kotor, 0, ',', '.') ?></p>
    <p><strong>Gaji Bersih:</strong> Rp<?= number_format($gaji_bersih, 0, ',', '.') ?></p>

    <div class="form-buttons">
        <a href="pengajuan_gaji.php" class="btn-batal">Kembali</a>
        <a href="ajukan_gaji.php?id_gaji=<?= $id_gaji ?>" class="btn-simpan">Ajukan</a>
    </div>
</div>
</body>
</html>

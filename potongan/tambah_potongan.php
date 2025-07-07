<?php 
session_start();
require __DIR__ . '/../config/koneksi.php';

// Cek apakah pengguna login dan levelnya admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_potongan = trim($_POST['nama_potongan']);
    $tarif = trim($_POST['tarif']);
    $keterangan = trim($_POST['keterangan']);

    // Generate ID otomatis (P001, P002, dst)
    $cek_id = $conn->query("SELECT id_potongan FROM potongan ORDER BY id_potongan DESC LIMIT 1");
    if ($row = $cek_id->fetch_assoc()) {
        $last = intval(substr($row['id_potongan'], 1)) + 1;
    } else {
        $last = 1;
    }
    $id_potongan = 'P' . str_pad($last, 3, '0', STR_PAD_LEFT);

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO potongan (id_potongan, nama_potongan, tarif, keterangan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $id_potongan, $nama_potongan, $tarif, $keterangan);

    if ($stmt->execute()) {
        $pesan = "✅ Data potongan berhasil ditambahkan.";
    } else {
        $pesan = "❌ Gagal menambahkan data potongan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Potongan</title>
    <link rel="stylesheet" href="../public/css/tambah_potongan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <h3>ADMIN</h3>
    </div>
    <ul class="nav">
        <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li class="has-submenu">
            <a href="#"><i class="fas fa-folder"></i> <span>Data Master</span> <i class="fas fa-chevron-down"></i></a>
            <ul class="submenu active">
                <li><a href="tambah_pengguna.php">Pengguna</a></li>
                <li><a href="tambah_jabatan.php">Jabatan</a></li>
                <li><a href="tambah_karyawan.php">Karyawan</a></li>
                <li><a href="tambah_presensi.php">Presensi</a></li>
                <li><a href="tambah_gapok.php">Gaji Pokok</a></li>
                <li><a href="tambah_tunjangan.php">Tunjangan</a></li>
                <li><a href="tambah_lembur.php">Lembur</a></li>
                <li><a href="tambah_potongan.php" class="active">Potongan</a></li>
            </ul>
        </li>

        <li class="has-submenu">
            <a href="#"><i class="fas fa-money-bill"></i> <span>Penggajian</span> <i class="fas fa-chevron-down"></i></a>
            <ul class="submenu active">
                <li><a href="pengajuan_gaji.php" class="active">Pengajuan Gaji</a></li>
                <li><a href="#">Daftar Gaji</a></li>
                <li><a href="#">Slip Gaji</a></li>
            </ul>
        </li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-file-alt"></i> <span>Laporan</span> <i class="fas fa-chevron-down"></i></a>
                <ul class="submenu">
                    <li><a href="#">Gaji Per Bulan</a></li>
                    <li><a href="#">Gaji Per Jabatan</a></li>
                </ul>
            </li>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content">
    <div class="topbar">
        <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
    </div>

    <div class="dashboard-header">
        <h2>Tambah Potongan</h2>
        <a href="daftar_potongan.php" class="btn-daftar">Daftar</a>
    </div>

    <?php if ($pesan): ?>
        <div class="notif"> <?= $pesan ?> </div>
    <?php endif; ?>

    <form method="POST" class="form-box">
        <h2>POTONGAN</h2>
        
        <label>Nama Potongan:</label>
        <select name="nama_potongan" required>
            <option value="">- Pilih Potongan -</option>
            <option value="BPJS">BPJS</option>
            <option value="Pinjaman">Absensi</option>
        </select>

        <label>Tarif (%):</label>
        <input type="number" name="tarif" step="0.01" required>

        <label>Keterangan:</label>
        <textarea name="keterangan" rows="4"></textarea>

        <div class="form-buttons">
            <a href="index.php" class="btn-batal">Batal</a>
            <button type="submit" class="btn-simpan">Simpan</button>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll(".has-submenu > a").forEach(function(menu) {
        menu.addEventListener("click", function(e) {
            e.preventDefault();
            const submenu = this.parentElement.querySelector(".submenu");
            submenu.classList.toggle("active");
        });
    });
</script>
</body>
</html>

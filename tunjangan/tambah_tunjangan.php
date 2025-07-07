<?php
session_start();
require '../config/koneksi.php';

// Batasi akses jika bukan admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = '';

// Ambil data jabatan untuk dropdown
$jabatan = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan");

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tunjangan = $_POST['nama_tunjangan'];
    $id_jabatan     = $_POST['jabatan'];
    $jumlah         = $_POST['jumlah'];
    $keterangan     = $_POST['keterangan'];

    // Generate ID otomatis (contoh: TJ001)
    $cek_id = $conn->query("SELECT id_tunjangan FROM tunjangan WHERE id_tunjangan LIKE 'TJ%' ORDER BY id_tunjangan DESC LIMIT 1");
    if ($row = $cek_id->fetch_assoc()) {
        $last = (int)substr($row['id_tunjangan'], 2) + 1;
    } else {
        $last = 1;
    }
    $id_tunjangan = 'TJ' . str_pad($last, 3, '0', STR_PAD_LEFT);

    // Insert data
    $stmt = $conn->prepare("INSERT INTO tunjangan (id_tunjangan, nama_tunjangan, id_jabatan, jumlah_tunjangan, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $id_tunjangan, $nama_tunjangan, $id_jabatan, $jumlah, $keterangan);

    if ($stmt->execute()) {
        $pesan = "✅ Tunjangan berhasil ditambahkan.";
    } else {
        $pesan = "❌ Gagal menambahkan tunjangan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Tunjangan</title>
    <link rel="stylesheet" href="../public/css/tambah_tunjangan.css">
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
                    <li><a href="tambah_tunjangan.php" class="active">Tunjangan</a></li>
                    <li><a href="tambah_lembur.php">Lembur</a></li>
                    <li><a href="tambah_potongan.php">Potongan</a></li>
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
            <h2>TUNJANGAN</h2>
            <a href="daftar_tunjangan.php" class="btn-daftar">Daftar</a>
        </div>

        <?php if ($pesan): ?>
            <div class="notif"><?= $pesan ?></div>
        <?php endif; ?>

        <form method="POST" class="form-box">
            <h2>TUNJANGAN</h2>
            <label>Nama Tunjangan</label>
            <input type="text" name="nama_tunjangan" required>

            <label>Jabatan</label>
            <select name="jabatan" required>
                <option value="">-Pilih Jabatan-</option>
                <?php while($row = $jabatan->fetch_assoc()): ?>
                    <option value="<?= $row['id_jabatan'] ?>"><?= $row['nama_jabatan'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Jumlah Tunjangan</label>
            <input type="number" name="jumlah" required>

            <label>Keterangan</label>
            <textarea name="keterangan" rows="4" required></textarea>

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

<?php
session_start();
require '../config/koneksi.php';

// Batasi akses jika bukan admin
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = '';

// Ambil data karyawan untuk dropdown
$karyawan = $conn->query("SELECT id_karyawan, nama_karyawan FROM karyawan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = $_POST['id_karyawan'];
    $bulan       = $_POST['bulan'];
    $tahun       = $_POST['tahun'];
    $hadir       = $_POST['hadir'];
    $sakit       = $_POST['sakit'];
    $izin        = $_POST['izin'];
    $alpha       = $_POST['alpha'];

    // Generate ID Presensi otomatis (PR001)
    $prefix = "PR";
    $cek_id = $conn->query("SELECT id_presensi FROM presensi WHERE id_presensi LIKE '$prefix%' ORDER BY id_presensi DESC LIMIT 1");
    if ($row = $cek_id->fetch_assoc()) {
        $last = intval(substr($row['id_presensi'], 2)) + 1;
    } else {
        $last = 1;
    }
    $id_presensi = $prefix . str_pad($last, 3, '0', STR_PAD_LEFT);

    // Simpan data presensi
    $stmt = $conn->prepare("INSERT INTO presensi (id_presensi, id_karyawan, bulan, tahun, hadir, sakit, izin, alpha) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiii", $id_presensi, $id_karyawan, $bulan, $tahun, $hadir, $sakit, $izin, $alpha);

    if ($stmt->execute()) {
        $pesan = "✅ Data presensi berhasil ditambahkan.";
    } else {
        $pesan = "❌ Gagal menambahkan presensi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Presensi</title>
    <link rel="stylesheet" href="../public/css/tambah_presensi.css">
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
                    <li><a href="tambah_presensi.php" class="active">Presensi</a></li>
                    <li><a href="tambah_gapok.php">Gaji Pokok</a></li>
                    <li><a href="tambah_tunjangan.php">Tunjangan</a></li>
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
            <h2>Tambah Presensi</h2>
            <a href="daftar_presensi.php" class="btn-daftar">Daftar</a>
        </div>

        <?php if ($pesan): ?>
            <div class="notif"><?= $pesan ?></div>
        <?php endif; ?>

        <form method="POST" class="form-box">
            <h2>PRESENSI</h2>
            <div class="form-grid">
                <div>
                    <label>Nama Karyawan:</label>
                    <select name="id_karyawan" required>
                        <option value="">-Pilih Karyawan-</option>
                        <?php while ($row = $karyawan->fetch_assoc()): ?>
                            <option value="<?= $row['id_karyawan'] ?>"><?= $row['nama_karyawan'] ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Bulan:</label>
                    <select name="bulan" required>
                        <option value="">-Pilih Bulan-</option>
                        <?php
                        $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        foreach ($bulan as $b) {
                            echo "<option value='$b'>$b</option>";
                        }
                        ?>
                    </select>

                    <label>Tahun:</label>
                    <input type="text" name="tahun" required>
                </div>

                <div>
                    <label>Hadir:</label>
                    <input type="number" name="hadir" required>

                    <label>Sakit:</label>
                    <input type="number" name="sakit" required>

                    <label>Izin:</label>
                    <input type="number" name="izin" required>

                    <label>Alpha/Tanpa Keterangan:</label>
                    <input type="number" name="alpha" required>
                </div>
            </div>

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

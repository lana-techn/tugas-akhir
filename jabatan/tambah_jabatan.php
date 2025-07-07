<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_jabatan = trim($_POST['nama_jabatan']);
    $pendidikan = $_POST['pendidikan'];

    if (!empty($nama_jabatan) && !empty($pendidikan)) {

        // Ambil dua huruf awal nama jabatan (tanpa spasi)
        $nama_clean = preg_replace('/\s+/', '', $nama_jabatan); // hapus spasi
        $prefix = strtoupper(substr($nama_clean, 0, 2)); // misal "Karyawan Produksi" → "KP"

        // Cek id terakhir dengan prefix tersebut
        $cek_id = $conn->query("SELECT Id_Jabatan FROM jabatan WHERE Id_Jabatan LIKE '$prefix%' ORDER BY Id_Jabatan DESC LIMIT 1");
        if ($row = $cek_id->fetch_assoc()) {
            $last = intval(substr($row['Id_Jabatan'], 2)) + 1;
        } else {
            $last = 1;
        }
        $id_jabatan = $prefix . str_pad($last, 3, '0', STR_PAD_LEFT); // Misal: KP001

        // Simpan ke database
        $stmt = $conn->prepare("INSERT INTO jabatan (Id_Jabatan, nama_jabatan, pendidikan) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $id_jabatan, $nama_jabatan, $pendidikan);

        if ($stmt->execute()) {
            $pesan = "✅ Jabatan berhasil ditambahkan dengan ID: $id_jabatan";
        } else {
            $pesan = "❌ Gagal menambahkan jabatan.";
        }
    } else {
        $pesan = "❌ Semua field wajib diisi.";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Jabatan</title>
    <link rel="stylesheet" href="../public/css/tambah_jabatan.css">
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
                    <li><a href="tambah_jabatan.php" class="active">Jabatan</a></li>
                    <li><a href="tambah_karyawan.php">Karyawan</a></li>
                    <li><a href="tambah_presensi.php">Presensi</a></li>
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
            <h2>Tambah Jabatan</h2>
            <a href="daftar_jabatan.php" class="btn-daftar">Daftar</a>
        </div>

        <?php if ($pesan): ?>
            <div class="notif">
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <div class="form-box">
            <h2>JABATAN</h2>
            <form method="post">
                <label for="nama_jabatan">Nama Jabatan:</label>
                <input type="text" name="nama_jabatan" id="nama_jabatan" required>

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
                    <a href="index.php" class="btn-batal">Batal</a>
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
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

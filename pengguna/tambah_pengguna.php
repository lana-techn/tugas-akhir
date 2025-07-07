<?php
session_start();
require '../config/koneksi.php';

// Cek apakah tabel pengguna masih kosong
$cek_user = $conn->query("SELECT COUNT(*) AS total FROM pengguna");
$data = $cek_user->fetch_assoc();
$pengguna_kosong = $data['total'] == 0;

// Jika pengguna sudah ada, batasi akses ke admin yang login
if (!$pengguna_kosong && (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $level    = $_POST['level'];
    $password = $_POST['password'];


    // Generate ID otomatis (a01, p01, k01)
    $prefix = strtolower(substr($level, 0, 1));
    $cek_id = $conn->query("SELECT Id_Pengguna FROM pengguna WHERE Id_Pengguna LIKE '$prefix%' ORDER BY Id_Pengguna DESC LIMIT 1");
    if ($row = $cek_id->fetch_assoc()) {
        $last = intval(substr($row['Id_Pengguna'], 1)) + 1;
    } else {
        $last = 1;
    }
    $id_pengguna = $prefix . str_pad($last, 2, '0', STR_PAD_LEFT);

    // Cek apakah email sudah ada
    $cek = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
    $cek->bind_param("s", $email);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $pesan = "❌ Email sudah terdaftar.";
    } else {
        // Masukkan data ke tabel
        $stmt = $conn->prepare("INSERT INTO pengguna (Id_Pengguna, email, level, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id_pengguna, $email, $level, $password);
        if ($stmt->execute()) {
            $pesan = "✅ Pengguna berhasil ditambahkan. ID: $id_pengguna";
            if ($pengguna_kosong) {
                header("Refresh:2; url=login.php");
            }
        } else {
            $pesan = "❌ Gagal menambahkan pengguna.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Pengguna</title>
    <link rel="stylesheet" href="../public/css/tambah_pengguna.css">
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
                    <li><a href="tambah_pengguna.php" class="active">Pengguna</a></li>
                    <li><a href="tambah_jabatan.php">Jabatan</a></li>
                    <li><a href="tambah_karyawan.php">Karyawan</a></li>
                    <li><a href="tambah_presensi,php">Presensi</a></li>
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
            <h2>Tambah Pengguna</h2>
            <a href="daftar_pengguna.php" class="btn-daftar">Daftar</a>
        </div>

        <?php if ($pesan): ?>
            <div class="notif">
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <div class="form-box">
            <h2>USER</h2>
            <form method="post">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>

                <label for="level">Level:</label>
                <select name="level" id="level" required>
                    <option value="">-- Pilih Level --</option>
                    <option value="admin">Admin</option>
                    <option value="karyawan">Karyawan</option>
                    <option value="pemilik">Pemilik</option>
                </select>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

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

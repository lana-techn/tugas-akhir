<?php
require_once 'config/koneksi.php';
requireLogin('admin');

$userInfo = getUserInfo();
$level = $userInfo['level'];
$email = $userInfo['email'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>ADMIN</h3>
        </div>
        <ul class="nav">
            <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>

            <li class="has-submenu">
                <a href="#"><i class="fas fa-folder"></i> <span>Data Master</span> <i class="fas fa-chevron-down"></i></a>
                <ul class="submenu">
                    <li><a href="pengguna/daftar_pengguna.php">Pengguna</a></li>
                    <li><a href="jabatan/daftar_jabatan.php">Jabatan</a></li>
                    <li><a href="karyawan/daftar_karyawan.php">Karyawan</a></li>
                    <li><a href="presensi/daftar_presensi.php">Presensi</a></li>
                    <li><a href="gaji/daftar_gapok.php">Gaji Pokok</a></li>
                    <li><a href="tunjangan/daftar_tunjangan.php">Tunjangan</a></li>
                    <li><a href="lembur/daftar_lembur.php">Lembur</a></li>
                    <li><a href="potongan/daftar_potongan.php">Potongan</a></li>
                </ul>
            </li>

            <li class="has-submenu">
              <a href="#"><i class="fas fa-money-bill"></i> <span>Penggajian</span> <i class="fas fa-chevron-down"></i></a>
                 <ul class="submenu">
                    <li><a href="gaji/pengajuan_gaji.php">Pengajuan Gaji</a></li>
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

            <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <span><?php echo !empty($email) ? $email : $level . '@gmail.com'; ?></span>
        </div>

        <div class="dashboard-header">
            <h2>Dashboard</h2>
        </div>

        <div class="notif">
            <p>Anda login sebagai <strong><?php echo strtoupper($level); ?></strong></p>
        </div>

        <div class="cards">
            <div class="card green">
                <div>
                    <h3>4</h3>
                    <p>Pengguna</p>
                </div>
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="card blue">
                <div>
                    <h3>2</h3>
                    <p>Karyawan</p>
                </div>
                <i class="fas fa-user"></i>
            </div>
            <div class="card red">
                <div>
                    <h3>3</h3>
                    <p>Jabatan</p>
                </div>
                <i class="fas fa-briefcase"></i>
            </div>
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

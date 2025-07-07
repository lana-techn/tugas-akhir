<?php
require_once 'config/koneksi.php';
requireLogin('pemilik');

$userInfo = getUserInfo();
$level = $userInfo['level'];
$email = $userInfo['email'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pemilik</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>PEMILIK</h3>
        </div>
        <ul class="nav">
            <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>

            <li><a href="#"><i class="fas fa-money-bill"></i> <span>Penggajian</span></a></li>

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
            <h2>Dashboard Pemilik</h2>
        </div>

        <div class="notif">
            <p>Anda login sebagai <strong><?php echo strtoupper($level); ?></strong></p>
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

<?php
session_start();
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'karyawan') {
    header("Location: login.php");
    exit;
}
$level = $_SESSION['level'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Karyawan</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>KARYAWAN</h3>
        </div>
        <ul class="nav">
            <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-money-check-alt"></i> <span>Gaji</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <span><?php echo $level; ?>@gmail.com</span>
        </div>

        <div class="dashboard-header">
            <h2>Dashboard Karyawan</h2>
        </div>

        <div class="notif">
            <p>Anda login sebagai <strong><?php echo strtoupper($level); ?></strong></p>
        </div>

        <div class="cards">
            <div class="card blue">
                <div>
                    <h3>Rp 3.000.000</h3>
                    <p>Total Gaji Bulan Ini</p>
                </div>
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <script>
        // Untuk submenu jika nanti ditambah
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

<?php
session_start();
session_destroy(); // Hapus semua sesi login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link rel="stylesheet" href="../public/css/logout.css">
</head>
<body>
    <div class="logout-container">
        <img src="logo.png" class="logo" alt="Logo Perusahaan">
        <h2>Anda telah logout</h2>
        <p>Login kembali untuk mengakses halaman web</p>
        <a href="login.php" class="btn">Kembali ke Login</a>
    </div>
</body>
</html>

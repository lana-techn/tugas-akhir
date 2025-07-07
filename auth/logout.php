<?php
session_start();
$_SESSION = array(); // Clear session data
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy(); // Hapus semua sesi login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        .logout-container {
            background: white;
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 1rem;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <img src="../assets/images/logo.png" class="logo" alt="Logo Perusahaan">
        <h2>Anda telah logout</h2>
        <p>Login kembali untuk mengakses halaman web</p>
        <a href="login.php" class="btn">Kembali ke Login</a>
    </div>
</body>
</html>

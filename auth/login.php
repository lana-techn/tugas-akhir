<?php
session_start();
require './config/koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Cek apakah password tersimpan sebagai hash atau teks biasa
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['email'] = $user['email'];
                $_SESSION['level'] = $user['level'];

                // Redirect sesuai level
                switch ($user['level']) {
                    case 'admin':
                        header("Location: ../index.php");
                        break;
                    case 'pemilik':
                        header("Location: ../index_pemilik.php");
                        break;
                    case 'karyawan':
                        header("Location: ../karyawan/index_karyawan.php");
                        break;
                    default:
                        $error = "Level pengguna tidak dikenali.";
                }
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Email tidak ditemukan!";
        }
    } else {
        $error = "Email dan password wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="../public/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../assets/images/logo.png" alt="Logo Perusahaan">
        </div>
        <h2>LOGIN</h2>
        <p class="subtitle">Silahkan login dengan akun anda</p>

        <?php if ($error): ?>
            <p style="color:red; text-align:center;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="text" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <div class="btn-wrapper">
                <button type="submit">LOGIN</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Cek password
            if (password_verify($password, $user['Password']) || $password === $user['Password']) {
                $_SESSION['email'] = $user['Email'];
                $_SESSION['level'] = $user['Level'];
                
                // Redirect sesuai level
                $userLevel = strtolower($user['Level']);
                
                switch ($userLevel) {
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
                        $error = "Level pengguna tidak dikenali!";
                }
                exit;
            } else {
                $error = "Email atau password salah!";
            }
        } else {
            $error = "Email atau password salah!";
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
    <link rel="stylesheet" type="text/css" href="../assets/css/login.css">
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

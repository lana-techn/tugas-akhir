<?php
// 1. SETUP
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, langsung arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $redirect_url = BASE_URL . '/index.php'; // Default redirect
    if (isset($_SESSION['level'])) {
        if (strtolower($_SESSION['level']) === 'pemilik') {
            $redirect_url = BASE_URL . '/index_pemilik.php';
        } elseif (strtolower($_SESSION['level']) === 'karyawan') {
            $redirect_url = BASE_URL . '/index_karyawan.php';
        }
    }
    header('Location: ' . $redirect_url);
    exit;
}

// 2. LOGIC HANDLING (POST REQUEST)
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Menggunakan nama tabel dan kolom PascalCase sesuai DB Anda
        $stmt = $conn->prepare("SELECT Id_Pengguna, Email, Password, Level FROM PENGGUNA WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Memeriksa password sebagai teks biasa (string comparison)
            if ($password === $user['Password']) {
                // Login berhasil
                session_regenerate_id(true);
                
                // Simpan data penting ke session menggunakan nama kolom dari DB
                $_SESSION['user_id'] = $user['Id_Pengguna'];
                $_SESSION['username'] = explode('@', $user['Email'])[0];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['level'] = $user['Level'];
                
                // Arahkan ke dashboard yang sesuai dengan level pengguna
                $redirect_url = BASE_URL . '/index.php'; // Default untuk Admin
                if (strtolower($user['Level']) === 'pemilik') {
                    $redirect_url = BASE_URL . '/index_pemilik.php';
                } elseif (strtolower($user['Level']) === 'karyawan') {
                    $redirect_url = BASE_URL . '/index_karyawan.php';
                }
                
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        // Pesan error yang sama untuk email tidak ditemukan atau password salah
        set_flash_message('error', 'Email atau password yang Anda masukkan salah.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    $conn->close();
}

// 3. VIEW
$page_title = 'Login';
generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .notif { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid transparent; }
        .notif-error { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm p-8 bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="flex flex-col items-center">
            <div class="w-20 h-20 mb-4">
                <img src="../assets/images/logo.png" alt="Logo Perusahaan" class="w-full h-full object-contain">
            </div>
            <h2 class="text-2xl font-bold text-gray-800">LOGIN</h2>
            <p class="text-sm text-gray-500 mb-6">Silakan login dengan akun Anda</p>
        </div>

        <?php 
            if(function_exists('display_flash_message')) {
                display_flash_message();
            }
        ?>
        
        <form method="POST" action="login.php" autocomplete="off">
            <?php if(function_exists('csrf_input')) csrf_input(); ?>
            <div class="mb-4">
                <label for="email" class="sr-only">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#4CAF50]" required>
            </div>
            <div class="mb-6">
                <label for="password" class="sr-only">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#4CAF50]" required>
            </div>
            <div class="text-center">
                <button type="submit" class="w-full bg-[#4CAF50] text-white font-bold py-2.5 px-4 rounded-md hover:bg-[#45a049] transition-colors duration-300 shadow-sm">
                    LOGIN
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="p-4 bg-green-50 rounded-lg border border-green-200 text-sm text-gray-700">
                <h4 class="font-bold text-center mb-3 text-green-800">✨ Akun Demo ✨</h4>
                <div class="space-y-3">
                    <div>
                        <p class="font-semibold text-gray-800">Role: Admin</p>
                        <p><span class="font-mono text-gray-500">Email:</span> admin123@gmail.com</p>
                        <p><span class="font-mono text-gray-500">Pass:</span> admin123</p>
                    </div>
                    <div class="pt-2 border-t border-green-100">
                        <p class="font-semibold text-gray-800">Role: Pemilik</p>
                        <p><span class="font-mono text-gray-500">Email:</span> pemilik1@gmail.com</p>
                        <p><span class="font-mono text-gray-500">Pass:</span> pemilik123</p>
                    </div>
                    <div class="pt-2 border-t border-green-100">
                        <p class="font-semibold text-gray-800">Role: Karyawan</p>
                        <p><span class="font-mono text-gray-500">Email:</span> karyawan1@gmail.com</p>
                        <p><span class="font-mono text-gray-500">Pass:</span> karyawan123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
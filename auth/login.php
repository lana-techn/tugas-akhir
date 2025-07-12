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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
        .notif { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left-width: 5px; font-size: 0.9rem; }
        .notif-error { background-color: #fef2f2; border-color: #ef4444; color: #b91c1c; }
        .login-bg {
            background-image: url('https://images.unsplash.com/photo-1593083739213-c9a72d733642?q=80&w=1887&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="flex min-h-screen">
        <div class="flex flex-1 flex-col justify-center items-center px-4 sm:px-6 lg:px-20 xl:px-28 py-12">
            <div class="w-full max-w-md">
                <div class="flex flex-col items-center mb-8">
                    <a href="#">
                        <img src="../assets/images/logo.png" alt="Logo Perusahaan" class="h-20 w-auto mb-4">
                    </a>
                    <h1 class="text-3xl font-bold font-poppins text-gray-800">Selamat Datang</h1>
                    <p class="text-gray-500 mt-2">Login untuk mengakses dashboard Anda</p>
                </div>

                <?php 
                    if(function_exists('display_flash_message')) {
                        display_flash_message();
                    }
                ?>
                
                <form method="POST" action="login.php" autocomplete="off" class="space-y-5">
                    <?php if(function_exists('csrf_input')) csrf_input(); ?>
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-solid fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" id="email" placeholder="Email" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-shadow duration-300" required>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                         <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                               <i class="fa-solid fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" placeholder="Password" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-shadow duration-300" required>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 shadow-lg hover:shadow-green-500/30 transform hover:-translate-y-0.5">
                            LOGIN
                        </button>
                    </div>
                </form>

                <div class="mt-10 pt-8 border-t border-gray-200">
                    <h4 class="font-bold text-center mb-4 text-gray-600">✨ Akun Demo ✨</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-100 p-3 rounded-lg border border-gray-200">
                            <p class="font-semibold text-gray-800">Admin</p>
                            <p class="text-xs text-gray-500">admin123@gmail.com</p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg border border-gray-200">
                            <p class="font-semibold text-gray-800">Pemilik</p>
                            <p class="text-xs text-gray-500">pemilik1@gmail.com</p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg border border-gray-200">
                            <p class="font-semibold text-gray-800">Karyawan</p>
                            <p class="text-xs text-gray-500">karyawan1@gmail.com</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="hidden lg:flex flex-1 items-center justify-center login-bg relative">
             <div class="absolute inset-0 bg-green-900 bg-opacity-50"></div>
             <div class="relative z-10 text-center text-white p-8">
                 <h2 class="text-4xl font-bold font-poppins">Sistem Penggajian</h2>
                 <p class="mt-4 text-lg max-w-md mx-auto">Manajemen penggajian menjadi lebih mudah, efisien, dan akurat.</p>
             </div>
        </div>
    </div>
</body>
</html>
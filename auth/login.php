<?php
// 1. SETUP
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $redirect_url = '../index.php'; // Default
    if (isset($_SESSION['level'])) {
        if (strtolower($_SESSION['level']) === 'pemilik') {
            $redirect_url = '../index_pemilik.php';
        } elseif (strtolower($_SESSION['level']) === 'karyawan') {
            $redirect_url = '../index_karyawan.php';
        }
    }
    header('Location: ' . $redirect_url);
    exit;
}

// 2. LOGIC HANDLING
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        set_flash_message('error', 'Email dan password wajib diisi.');
    } else {
        $stmt = $conn->prepare("SELECT Id_Pengguna, Email, Password, Level FROM PENGGUNA WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($password === $user['Password']) { // Asumsi password plain text
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['Id_Pengguna'];
                $_SESSION['username'] = explode('@', $user['Email'])[0];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['level'] = $user['Level'];
                
                $redirect_url = '../index.php'; // Default
                if (strtolower($user['Level']) === 'pemilik') {
                    $redirect_url = '../index_pemilik.php';
                } elseif (strtolower($user['Level']) === 'karyawan') {
                    $redirect_url = '../index_karyawan.php';
                }
                
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        set_flash_message('error', 'Email atau password yang Anda masukkan salah.');
    }
    $conn->close();
    header('Location: login.php');
    exit;
}

// 3. VIEW
$page_title = 'Login';
generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .gradient-bg {
            background-color: #10B981;
            background-image: linear-gradient(135deg, #059669, #10B981, #34D399 );
        }
        .form-input {
            background-color: #F3F4F6;
            border-radius: 9999px;
            border: 2px solid transparent;
            padding: 0.75rem 3rem; /* Disesuaikan untuk ikon di kedua sisi */
            width: 100%;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            background-color: white;
            border-color: #10B981;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .login-button {
            background-color: #059669;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px 0 rgba(16, 185, 129, 0.3);
        }
        .login-button:hover {
            background-color: #047857;
            transform: translateY(-2px);
            box-shadow: 0 7px 20px 0 rgba(16, 185, 129, 0.4);
        }
        .notif { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left-width: 4px; }
        .notif-error { background-color: #fef2f2; border-color: #ef4444; color: #b91c1c; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-5xl min-h-[600px] bg-white shadow-2xl rounded-3xl overflow-hidden lg:grid lg:grid-cols-2">
            
            <!-- Kolom Kiri (Informasi) -->
            <div class="relative hidden lg:flex flex-col justify-center items-start p-12 text-white gradient-bg">
                <div class="absolute -bottom-24 -right-20 w-48 h-48 bg-white/10 rounded-full"></div>
                <div class="absolute -top-20 -left-24 w-48 h-48 bg-white/10 rounded-full"></div>
                <a href="#" class="mb-8"><img src="../assets/images/logo.png" alt="Logo" class="h-12"></a>
                <h1 class="text-4xl font-bold leading-tight">Sistem Informasi Penggajian Karyawan</h1>
                <p class="mt-4 text-lg text-white/80">Manajemen penggajian menjadi lebih mudah, efisien, dan akurat.</p>
                <div class="mt-auto text-sm text-white/60">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</div>
            </div>

            <!-- Kolom Kanan (Form Login) -->
            <div class="flex flex-col justify-center p-8 sm:p-16">
                <div class="w-full max-w-md mx-auto">
                    <div class="text-center lg:text-left mb-10">
                        <h2 class="text-3xl font-bold text-gray-800">User Login</h2>
                        <p class="text-gray-500 mt-2">Akses dashboard Anda di sini.</p>
                    </div>

                    <?php if(function_exists('display_flash_message')) { display_flash_message(); } ?>

                    <form method="POST" action="login.php" autocomplete="off" class="space-y-6">
                        <?php if(function_exists('csrf_input')) csrf_input(); ?>
                        
                        <!-- Input Email -->
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" placeholder="Email" class="form-input !pl-12 !pr-4" required>
                        </div>
                        
                        <!-- PERUBAHAN: Input Password dengan Ikon Mata -->
                        <div x-data="{ show: false }" class="relative">
                            <i class="fa-solid fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input :type="show ? 'text' : 'password'" name="password" placeholder="Password" class="form-input !pl-12 !pr-12" required>
                            <button type="button" @click="show = !show" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-500 hover:text-emerald-600 cursor-pointer">
                                <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'" x-cloak></i>
                            </button>
                        </div>
                        
                        <!-- Tombol Login -->
                        <div class="pt-4">
                            <button type="submit" class="w-full font-semibold text-white py-3 px-4 rounded-full login-button">
                                LOGIN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

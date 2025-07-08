<?php
// 1. SETUP
require_once __DIR__ . '/functions.php';

// Jika sudah login, langsung arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $redirect_url = BASE_URL . '/index.php'; // Default redirect
    if ($_SESSION['level'] === 'pemilik') {
        $redirect_url = BASE_URL . '/index_pemilik.php';
    } elseif ($_SESSION['level'] === 'karyawan') {
        $redirect_url = BASE_URL . '/karyawan/karyawan.php?action=dashboard'; // Asumsi ada dashboard karyawan
    }
    header('Location: ' . $redirect_url);
    exit;
}

// 2. LOGIC HANDLING (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }
    
    $conn = db_connect();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        set_flash_message('error', 'Email dan password wajib diisi.');
    } else {
        $stmt = $conn->prepare("SELECT id_pengguna, email, password, level FROM pengguna WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // PERBAIKAN KRUSIAL: Hanya verifikasi password yang sudah di-hash
            if (password_verify($password, $user['password'])) {
                // Regenerasi session ID untuk mencegah session fixation
                session_regenerate_id(true);
                
                // Simpan data penting ke session
                $_SESSION['user_id'] = $user['id_pengguna'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['level'] = $user['level'];
                
                // Arahkan ke dashboard yang sesuai
                $redirect_url = BASE_URL . '/index.php'; // Default
                if ($user['level'] === 'pemilik') {
                    $redirect_url = BASE_URL . '/index_pemilik.php';
                } elseif ($user['level'] === 'karyawan') {
                    $redirect_url = BASE_URL . '/karyawan/index_karyawan.php';
                }
                
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        // Pesan error yang sama untuk email tidak ditemukan atau password salah
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .notif { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid transparent; }
        .notif-error { background-color: #fee2e2; border-color: #fca5a5; color: #991b1b; }
    </style>
</head>
<body class="bg-gradient-to-br from-white to-[#b2f2bb] flex items-center justify-center min-h-screen font-['Segoe_UI',_sans-serif]">
    <div class="w-full max-w-sm p-8 bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="flex flex-col items-center">
            <div class="w-24 h-24 mb-4 border-2 border-[#88cc88] rounded-full overflow-hidden flex items-center justify-center">
                <img src="../assets/images/logo.png" alt="Logo Perusahaan" class="w-full h-full object-cover">
            </div>
            <h2 class="text-2xl font-bold text-gray-800">LOGIN</h2>
            <p class="text-sm text-gray-500 mb-6">Silakan login dengan akun Anda</p>
        </div>

        <?php display_flash_message(); ?>
        
        <form method="POST" action="login.php" autocomplete="off">
            <?php csrf_input(); ?>
            <div class="mb-4">
                <label for="email" class="sr-only">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#4CAF50]" required>
            </div>
            <div class="mb-6">
                <label for="password" class="sr-only">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#4CAF50]" required>
            </div>
            <div class="text-center">
                <button type="submit" class="w-full bg-[#4CAF50] text-white font-bold py-2 px-4 rounded-md hover:bg-[#45a049] transition-colors duration-300">
                    LOGIN
                </button>
            </div>
        </form>
    </div>
</body>
</html>
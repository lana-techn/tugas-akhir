<?php
// Memulai sesi jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat file konfigurasi
require_once dirname(__DIR__) . '/config/config.php';

/**
 * Membuat koneksi ke database menggunakan mysqli.
 * @return mysqli
 */
function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Memeriksa apakah pengguna sudah login dan memiliki level yang sesuai.
 * @param string|null $required_level Level yang dibutuhkan ('admin', 'pemilik', 'karyawan').
 */
function require_login($required_level = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
    if ($required_level && $_SESSION['level'] !== $required_level) {
        http_response_code(403);
        die("Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.");
    }
}

/**
 * Mengatur pesan flash untuk ditampilkan di halaman berikutnya.
 * @param string $type ('success', 'error', 'info')
 * @param string $message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Menampilkan pesan flash dengan styling TailwindCSS.
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = htmlspecialchars($_SESSION['flash_message']['message'], ENT_QUOTES, 'UTF-8');
        
        $colors = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error'   => 'bg-red-100 border-red-400 text-red-700',
            'info'    => 'bg-blue-100 border-blue-400 text-blue-700'
        ];
        $alertClass = $colors[$type] ?? $colors['info'];
        
        echo "<div class='notif {$alertClass}'>{$message}</div>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Fungsi untuk sanitasi output HTML (mencegah XSS).
 * @param string|null $string
 * @return string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fungsi untuk proteksi CSRF.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_input() {
    generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash_message('error', 'Sesi tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
        // Hapus token agar dibuat ulang
        unset($_SESSION['csrf_token']);
        return false;
    }
    // Hapus token setelah digunakan untuk mencegah replay attacks
    unset($_SESSION['csrf_token']);
    return true;
}
?>
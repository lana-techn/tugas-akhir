<?php
// Memulai sesi jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat file konfigurasi
require_once dirname(__DIR__) . '/config/koneksi.php';

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
    if ($required_level && strtolower($_SESSION['level']) !== strtolower($required_level)) {
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
        
        $icon = '';
        if ($type === 'success') $icon = '<i class="fa-solid fa-check-circle mr-3"></i>';
        if ($type === 'error') $icon = '<i class="fa-solid fa-times-circle mr-3"></i>';

        $colors = [
            'success' => 'bg-green-50 border-green-400 text-green-800',
            'error'   => 'bg-red-50 border-red-400 text-red-800',
            'info'    => 'bg-blue-50 border-blue-400 text-blue-800'
        ];
        $alertClass = $colors[$type] ?? $colors['info'];
        
        echo "<div class='notif flex items-center p-4 mb-4 text-sm rounded-lg {$alertClass}'>{$icon}<span>{$message}</span></div>";
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
    // Memvalidasi hanya jika token ada di POST. Mencegah error jika form tidak punya CSRF.
    if (isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            set_flash_message('error', 'Sesi tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
            unset($_SESSION['csrf_token']);
            return false;
        }
        // Token hanya digunakan sekali, hapus setelah validasi sukses.
        unset($_SESSION['csrf_token']);
        return true;
    }
    // Jika tidak ada token di POST, anggap validasi tidak diperlukan untuk form ini.
    return true; 
}


/**
 * Membuat link pagination dengan gaya TailwindCSS dan logika elipsis.
 * @param int $current_page Halaman saat ini.
 * @param int $total_pages Jumlah total halaman.
 * @param string $base_url URL dasar untuk link pagination.
 * @param array $params Parameter query tambahan.
 * @return string HTML untuk komponen pagination.
 */
function generate_pagination_links($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }

    $query_string = http_build_query($params);
    $base_url .= '?' . $query_string;

    $html = '<nav class="flex items-center justify-between mt-6">';
    $html .= '<span class="text-sm text-gray-500">Halaman <strong>' . $current_page . '</strong> dari <strong>' . $total_pages . '</strong></span>';
    $html .= '<ul class="inline-flex items-center -space-x-px">';

    // Tombol Previous
    $prev_class = ($current_page > 1) 
        ? 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700' 
        : 'text-gray-400 bg-gray-50 cursor-not-allowed';
    $prev_href = ($current_page > 1) ? 'href="' . $base_url . '&page=' . ($current_page - 1) . '"' : '';
    $html .= '<li><a ' . $prev_href . ' class="px-3 py-2 ml-0 leading-tight border border-gray-300 rounded-l-lg ' . $prev_class . '"><i class="fa-solid fa-chevron-left text-xs"></i></a></li>';

    // Tombol Halaman dengan Logika Elipsis
    $window = 2; // Jumlah halaman yang ditampilkan di sekitar halaman aktif
    if ($total_pages <= (2 * $window) + 3) {
        // Tampilkan semua jika total halaman sedikit
        for ($i = 1; $i <= $total_pages; $i++) {
            $html .= render_page_number($i, $current_page, $base_url);
        }
    } else {
        // Tampilkan halaman pertama
        $html .= render_page_number(1, $current_page, $base_url);
        
        // Elipsis kiri
        if ($current_page > $window + 2) {
            $html .= '<li><span class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }
        
        // Jendela halaman
        for ($i = max(2, $current_page - $window); $i <= min($total_pages - 1, $current_page + $window); $i++) {
            $html .= render_page_number($i, $current_page, $base_url);
        }

        // Elipsis kanan
        if ($current_page < $total_pages - $window - 1) {
            $html .= '<li><span class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        // Tampilkan halaman terakhir
        $html .= render_page_number($total_pages, $current_page, $base_url);
    }

    // Tombol Next
    $next_class = ($current_page < $total_pages) 
        ? 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700' 
        : 'text-gray-400 bg-gray-50 cursor-not-allowed';
    $next_href = ($current_page < $total_pages) ? 'href="' . $base_url . '&page=' . ($current_page + 1) . '"' : '';
    $html .= '<li><a ' . $next_href . ' class="px-3 py-2 leading-tight border border-gray-300 rounded-r-lg ' . $next_class . '"><i class="fa-solid fa-chevron-right text-xs"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Fungsi pembantu untuk merender tombol nomor halaman.
 * @param int $page_num Nomor halaman yang akan dirender.
 * @param int $current_page Halaman aktif saat ini.
 * @param string $base_url URL dasar.
 * @return string HTML untuk satu tombol nomor halaman.
 */
function render_page_number($page_num, $current_page, $base_url) {
    if ($page_num == $current_page) {
        return '<li><span aria-current="page" class="z-10 px-3 py-2 leading-tight text-white bg-green-600 border border-green-600">' . $page_num . '</span></li>';
    } else {
        return '<li><a href="' . $base_url . '&page=' . $page_num . '" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $page_num . '</a></li>';
    }
}
?>
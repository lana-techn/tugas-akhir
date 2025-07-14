<?php
// Pastikan sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil level pengguna dan halaman saat ini
$user_level = strtolower($_SESSION['level'] ?? '');
$current_page_filename = basename($_SERVER['PHP_SELF']);
$current_action = $_GET['action'] ?? '';

// Definisikan struktur navigasi
$navigation = [
    // Menu Umum
    ['name' => 'Dashboard', 'href' => 'dashboard', 'icon' => 'fa-solid fa-house-chimney', 'roles' => ['admin', 'pemilik', 'karyawan']],

    // Menu Admin
    ['group' => 'Data Master', 'roles' => ['admin']],
    ['name' => 'Pengguna', 'href' => 'pengguna.php', 'icon' => 'fa-solid fa-user-shield', 'roles' => ['admin']],
    ['name' => 'Jabatan', 'href' => 'jabatan.php', 'icon' => 'fa-solid fa-briefcase', 'roles' => ['admin']],
    ['name' => 'Karyawan', 'href' => 'karyawan.php', 'icon' => 'fa-solid fa-users', 'roles' => ['admin']],
    ['name' => 'Presensi', 'href' => 'presensi.php', 'icon' => 'fa-solid fa-user-check', 'roles' => ['admin']],

    ['group' => 'Penggajian', 'roles' => ['admin']],
    ['name' => 'Gaji Pokok', 'href' => 'salary.php', 'icon' => 'fa-solid fa-money-check-dollar', 'roles' => ['admin']],
    ['name' => 'Tunjangan', 'href' => 'tunjangan.php', 'icon' => 'fa-solid fa-gift', 'roles' => ['admin']],
    ['name' => 'Lembur', 'href' => 'lembur.php', 'icon' => 'fa-solid fa-clock', 'roles' => ['admin']],
    ['name' => 'Potongan', 'href' => 'potongan.php', 'icon' => 'fa-solid fa-scissors', 'roles' => ['admin']],
    ['name' => 'Pengajuan Gaji', 'href' => 'pengajuan_gaji.php', 'icon' => 'fa-solid fa-file-invoice-dollar', 'roles' => ['admin']],

    // Menu Pemilik
    ['group' => 'Manajemen Pemilik', 'roles' => ['pemilik']],
    // PERBAIKAN: Pastikan href tidak diawali dengan '/' dan path-nya benar
    ['name' => 'Persetujuan Gaji', 'href' => 'pemilik/penggajian_pemilik.php', 'icon' => 'fa-solid fa-check-to-slot', 'roles' => ['pemilik']],
    ['name' => 'Laporan', 'href' => 'pemilik/laporan.php', 'icon' => 'fa-solid fa-chart-pie', 'roles' => ['pemilik']],
    
    // Menu Karyawan
    ['group' => 'Area Pegawai', 'roles' => ['karyawan']],
    // PERBAIKAN: Pastikan href tidak diawali dengan '/' dan path-nya benar
    ['name' => 'Slip Gaji', 'href' => 'karyawan/slip_gaji.php', 'icon' => 'fa-solid fa-receipt', 'roles' => ['karyawan']]
];
?>
<div class="flex h-full flex-col bg-white border-r border-gray-200">
    <div class="flex h-20 shrink-0 items-center justify-center px-4">
        <a href="<?= BASE_URL ?>/index.php">
             <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-16 w-auto">
        </a>
    </div>

    <nav class="flex flex-1 flex-col mt-2">
        <ul role="list" class="flex flex-1 flex-col gap-y-7 px-4">
            <li>
                <ul role="list" class="space-y-1.5">
                    <?php foreach ($navigation as $item): ?>
                        <?php if (in_array($user_level, $item['roles'])): ?>
                            <?php if (isset($item['group'])): ?>
                                <div class="px-3 pt-4 pb-2 text-xs font-semibold uppercase text-gray-400 tracking-wider">
                                    <?= e($item['group']) ?>
                                </div>
                            <?php else: ?>
                                <?php
                                    $link_href = '';
                                    $is_current = false;

                                    if ($item['href'] === 'dashboard') {
                                        if ($user_level === 'pemilik') {
                                            $link_href = BASE_URL . '/index_pemilik.php';
                                            $is_current = ($current_page_filename === 'index_pemilik.php');
                                        } elseif ($user_level === 'karyawan') {
                                            $link_href = BASE_URL . '/index_karyawan.php';
                                            $is_current = ($current_page_filename === 'index_karyawan.php');
                                        } else { // Admin
                                            $link_href = BASE_URL . '/index.php';
                                            $is_current = ($current_page_filename === 'index.php');
                                        }
                                    } else {
                                        // Logika pembuatan URL yang benar
                                        $base_folder = '/pages/';
                                        $link_href = BASE_URL . $base_folder . $item['href'];
                                        
                                        $href_parts = explode('?', $item['href']);
                                        $item_filename_path = $href_parts[0];
                                        
                                        $current_path_parts = explode('/', $_SERVER['PHP_SELF']);
                                        $current_page_simple = end($current_path_parts);

                                        $item_path_parts = explode('/', $item_filename_path);
                                        $item_filename_simple = end($item_path_parts);
                                        
                                        $is_current = ($current_page_simple === $item_filename_simple);
                                    }
                                ?>
                                <li>
                                    <a href="<?= $link_href ?>"
                                       class="<?= $is_current
                                           ? 'bg-green-600 text-white shadow-md'
                                           : 'text-gray-600 hover:text-green-700 hover:bg-green-50';
                                       ?> group flex items-center gap-x-3 rounded-md p-3 text-sm leading-6 font-semibold transition-all duration-200">

                                        <i class="<?= e($item['icon']) ?> w-6 h-6 text-center text-base <?= $is_current ? 'text-white' : 'text-gray-400 group-hover:text-green-600'; ?>"></i>
                                        <?= e($item['name']) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li class="mt-auto mb-4">
                 <a href="<?= BASE_URL ?>/auth/logout.php" class="group flex items-center gap-x-3 rounded-md p-3 text-sm font-semibold leading-6 text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors duration-200">
                    <i class="fa-solid fa-right-from-bracket w-6 h-6 text-center text-base text-gray-400 group-hover:text-red-500"></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</div>
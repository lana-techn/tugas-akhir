<?php
// Pastikan sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil level pengguna dan halaman saat ini
$user_level = strtolower($_SESSION['level'] ?? '');
$current_page = basename($_SERVER['PHP_SELF']);

// Definisikan struktur navigasi (tidak ada perubahan di sini)
$navigation = [
    // ... (array navigasi Anda tetap sama)
    // Menu Umum
    ['name' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-solid fa-gauge-high', 'roles' => ['admin', 'pemilik']],
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
    // Menu Pemilik
    ['name' => 'Penggajian', 'href' => 'penggajian_pemilik.php', 'icon' => 'fa-solid fa-money-bill-wave', 'roles' => ['pemilik']],
    ['name' => 'Laporan', 'href' => 'laporan.php', 'icon' => 'fa-solid fa-file-alt', 'roles' => ['pemilik']],
];

?>
<div class="flex h-full flex-col bg-white">
    <div class="flex h-20 shrink-0 items-center justify-center px-4 border-b border-gray-200">
        <a href="<?= BASE_URL ?>/index.php">
             <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-12 w-auto">
        </a>
    </div>

    <nav class="flex flex-1 flex-col mt-4">
        <ul role="list" class="flex flex-1 flex-col gap-y-7 px-4">
            <li>
                <ul role="list" class="space-y-2">
                    <?php foreach ($navigation as $item): ?>
                        <?php if (in_array($user_level, $item['roles'])): ?>
                            <?php if (isset($item['group'])): ?>
                                <div class="px-2 pt-4 pb-2 text-xs font-bold uppercase text-gray-500">
                                    <?= e($item['group']) ?>
                                </div>
                            <?php else: ?>
                                <?php 
                                    $is_current = ($current_page === $item['href']);
                                    $link_href = (strpos($item['href'], '.php') !== false) ? BASE_URL . '/pages/' . $item['href'] : BASE_URL . '/' . $item['href'];
                                    if ($item['href'] === 'index.php') {
                                        $link_href = BASE_URL . '/index.php';
                                    }
                                ?>
                                <li>
                                    <a href="<?= $link_href ?>"
                                       class="<?= $is_current
                                           ? 'bg-green-600 text-white shadow-sm' // <-- WARNA AKTIF DIUBAH
                                           : 'text-gray-600 hover:text-green-700 hover:bg-green-50'; // <-- WARNA HOVER DIUBAH
                                       ?> group flex items-center gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200">
                                        
                                        <span class="flex h-6 w-6 items-center justify-center">
                                            <i class="<?= e($item['icon']) ?> <?= $is_current ? 'text-white' : 'text-gray-400 group-hover:text-green-600'; ?> text-base"></i>
                                        </span>
                                        <?= e($item['name']) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li class="mt-auto mb-4">
                 <a href="<?= BASE_URL ?>/auth/logout.php" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors duration-200">
                    <span class="flex h-6 w-6 items-center justify-center">
                       <i class="fa-solid fa-right-from-bracket text-gray-400 group-hover:text-red-500"></i>
                    </span>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</div>
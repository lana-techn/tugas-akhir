<?php
$page_title = 'Dashboard Admin';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_login('admin');
?>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-[#2e7d32]">Dashboard</h1>
        <span class="text-sm text-gray-600"><?= e($_SESSION['email'] ?? '') ?></span>
    </div>

    <div class="notif notif-success">
        <p>Anda login sebagai <strong><?= e(strtoupper($_SESSION['level'])) ?></strong></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-[#43a047] text-white p-6 rounded-lg shadow-md flex justify-between items-center">
            <div>
                <h3 class="text-3xl font-bold">4</h3>
                <p>Pengguna</p>
            </div>
            <i class="fas fa-user-plus fa-3x opacity-70"></i>
        </div>
        <div class="bg-[#039be5] text-white p-6 rounded-lg shadow-md flex justify-between items-center">
            <div>
                <h3 class="text-3xl font-bold">2</h3>
                <p>Karyawan</p>
            </div>
            <i class="fas fa-users fa-3x opacity-70"></i>
        </div>
        <div class="bg-[#e53935] text-white p-6 rounded-lg shadow-md flex justify-between items-center">
            <div>
                <h3 class="text-3xl font-bold">3</h3>
                <p>Jabatan</p>
            </div>
            <i class="fas fa-briefcase fa-3x opacity-70"></i>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
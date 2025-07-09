<?php
$page_title = 'Dashboard Karyawan';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_login('karyawan');
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
    
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
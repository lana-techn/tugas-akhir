<?php
// Ambil level pengguna dari sesi untuk menentukan menu
$user_level = $_SESSION['level'] ?? '';
$current_page = $current_page ?? ''; // Halaman aktif saat ini
?>
<div class="w-64 flex-shrink-0 bg-gradient-to-b from-[#b2f2bb] to-white text-black">
    <div class="p-5 bg-[#98eba3] text-center">
        <h3 class="text-xl font-bold uppercase"><?= e($user_level) ?></h3>
    </div>
    <nav class="mt-4">
        <a href="<?= BASE_URL ?>/index.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 <?= ($current_page === 'dashboard') ? 'bg-[#388e3c] text-white' : '' ?>">
            <i class="fas fa-home w-6 text-center"></i> <span class="ml-2">Dashboard</span>
        </a>

        <?php if (strtolower($user_level) === 'admin'): ?>
        <div class="px-5 mt-4 mb-2 text-xs uppercase text-gray-500 font-bold">Data Master</div>
        <a href="<?= BASE_URL ?>/pages/pengguna.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-user-shield w-6 text-center"></i> <span class="ml-2">Pengguna</span></a>
        <a href="<?= BASE_URL ?>/pages/jabatan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-briefcase w-6 text-center"></i> <span class="ml-2">Jabatan</span></a>
        <a href="<?= BASE_URL ?>/pages/karyawan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-users w-6 text-center"></i> <span class="ml-2">Karyawan</span></a>
        <a href="<?= BASE_URL ?>/pages/presensi.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-user-check w-6 text-center"></i> <span class="ml-2">Presensi</span></a>
        
        <div class="px-5 mt-4 mb-2 text-xs uppercase text-gray-500 font-bold">Penggajian</div>
        <a href="<?= BASE_URL ?>/pages/salary.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-money-check-dollar w-6 text-center"></i> <span class="ml-2">Gaji Pokok</span></a>
        <a href="<?= BASE_URL ?>/pages/tunjangan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-gift w-6 text-center"></i> <span class="ml-2">Tunjangan</span></a>
        <a href="<?= BASE_URL ?>/pages/lembur.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-clock w-6 text-center"></i> <span class="ml-2">Lembur</span></a>
        <a href="<?= BASE_URL ?>/pages/potongan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-cut w-6 text-center"></i> <span class="ml-2">Potongan</span></a>
        
        <?php endif; ?>

        <?php if (strtolower($user_level) === 'pemilik'): ?>
        <a href="#" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-money-bill w-6 text-center"></i> <span>Penggajian</span></a>
        <a href="#" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-file-alt w-6 text-center"></i> <span>Laporan</span></a>
        <?php endif; ?>
        
        <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4">
            <i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span>
        </a>
    </nav>
</div>

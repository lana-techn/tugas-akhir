<?php
$page_title = 'Dashboard Karyawan';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
require_login('karyawan');

$conn = db_connect();

// Ambil data karyawan yang login
$id_pengguna = $_SESSION['user_id'];
$stmt_karyawan = $conn->prepare("SELECT Nama_Karyawan, Id_Jabatan FROM KARYAWAN WHERE Id_Pengguna = ?");
$stmt_karyawan->bind_param("i", $id_pengguna);
$stmt_karyawan->execute();
$karyawan_data = $stmt_karyawan->get_result()->fetch_assoc();
$stmt_karyawan->close();

$nama_karyawan = $karyawan_data['Nama_Karyawan'] ?? 'Karyawan';

// Ambil data gaji terakhir
$gaji_terakhir = null;
if ($karyawan_data) {
    $stmt_gaji = $conn->prepare(
        "SELECT g.Tgl_Gaji, g.Gaji_Bersih 
         FROM GAJI g 
         JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
         WHERE k.Id_Pengguna = ? 
         ORDER BY g.Tgl_Gaji DESC LIMIT 1"
    );
    $stmt_gaji->bind_param("i", $id_pengguna);
    $stmt_gaji->execute();
    $gaji_terakhir = $stmt_gaji->get_result()->fetch_assoc();
    $stmt_gaji->close();
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-[#2e7d32]">Dashboard</h1>
        <span class="text-sm text-gray-600">Selamat datang, <?= e($nama_karyawan) ?>!</span>
    </div>

    <?php display_flash_message(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card Gaji Terakhir -->
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
            <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                <i class="fa-solid fa-money-bill-wave text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Gaji Terakhir Anda</p>
                <?php if ($gaji_terakhir): ?>
                    <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($gaji_terakhir['Gaji_Bersih'], 0, ',', '.') ?></p>
                    <p class="text-xs text-gray-500">Periode: <?= e(date('F Y', strtotime($gaji_terakhir['Tgl_Gaji']))) ?></p>
                <?php else: ?>
                    <p class="text-lg font-bold text-gray-800">Belum ada data gaji.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Lihat Slip Gaji -->
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                <i class="fa-solid fa-file-invoice-dollar text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Akses Slip Gaji Anda</p>
                <a href="slip_gaji.php" class="text-blue-600 hover:underline font-semibold">Lihat Slip Gaji</a>
            </div>
        </div>

        <!-- Card Informasi Lain (Opsional) -->
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
            <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-full">
                <i class="fa-solid fa-circle-info text-yellow-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pusat Informasi</p>
                <p class="text-gray-800 font-semibold">Hubungi HRD untuk pertanyaan lebih lanjut.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

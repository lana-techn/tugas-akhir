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

// Ambil data gaji terakhir yang sudah disetujui
$gaji_terakhir = null;
if ($karyawan_data) {
    $stmt_gaji = $conn->prepare(
        "SELECT g.Tgl_Gaji, g.Gaji_Bersih 
         FROM GAJI g 
         JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
         WHERE k.Id_Pengguna = ? AND g.Status = 'Disetujui'
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

<main class="flex-1 p-6 sm:p-8 bg-gray-50">
    <div class="bg-gradient-to-r from-green-600 to-emerald-700 text-white p-8 rounded-xl shadow-lg mb-8">
        <h1 class="text-3xl md:text-4xl font-bold font-poppins">Halo, <?= e(explode(' ', $nama_karyawan)[0]) ?>!</h1>
        <p class="mt-2 text-lg text-green-100">Selamat datang di dashboard pribadi Anda. Berikut adalah ringkasan informasi Anda.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 bg-green-100 p-4 rounded-full">
                    <i class="fa-solid fa-money-bill-wave text-green-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Gaji Terakhir Diterima</p>
                    <?php if ($gaji_terakhir): ?>
                        <p class="text-3xl font-bold text-gray-800 mt-1">Rp <?= number_format($gaji_terakhir['Gaji_Bersih'], 0, ',', '.') ?></p>
                        <p class="text-xs text-gray-500 mt-1">Periode: <?= e(date('F Y', strtotime($gaji_terakhir['Tgl_Gaji']))) ?></p>
                    <?php else: ?>
                        <p class="text-lg font-semibold text-gray-700 mt-2">Belum ada data gaji yang disetujui.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-300 flex flex-col justify-between">
            <div>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 bg-blue-100 p-4 rounded-full">
                        <i class="fa-solid fa-file-invoice-dollar text-blue-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Akses Cepat</p>
                        <p class="text-xl font-bold text-gray-800 mt-1">Slip Gaji Anda</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-3">Lihat rincian pendapatan dan potongan gaji Anda kapan saja.</p>
            </div>
            <a href="pages/karyawan/slip_gaji.php" class="mt-4 block text-center bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300">
                Lihat Detail Slip Gaji <i class="fa-solid fa-arrow-up-right-from-square ml-2"></i>
            </a>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500 hover:shadow-xl transition-shadow duration-300">
             <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 bg-yellow-100 p-4 rounded-full">
                    <i class="fa-solid fa-circle-info text-yellow-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm font-medium">Pusat Informasi</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">Bantuan & Dukungan</p>
                    <p class="text-sm text-gray-600 mt-3">Hubungi HRD atau bagian administrasi jika Anda memiliki pertanyaan terkait penggajian atau data pribadi.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
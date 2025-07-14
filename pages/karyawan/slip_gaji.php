<?php
$page_title = 'Slip Gaji';
$current_page = 'slip_gaji';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('karyawan');

$conn = db_connect();
$slip_data = null;
$id_karyawan_login = '';

if (isset($_SESSION['user_id'])) {
    $id_pengguna = $_SESSION['user_id'];

    $stmt_karyawan = $conn->prepare("SELECT Id_Karyawan FROM KARYAWAN WHERE Id_Pengguna = ?");
    $stmt_karyawan->bind_param("s", $id_pengguna);
    $stmt_karyawan->execute();
    $karyawan_data = $stmt_karyawan->get_result()->fetch_assoc();
    $stmt_karyawan->close();
    
    if ($karyawan_data) {
        $id_karyawan_login = $karyawan_data['Id_Karyawan'];

        $stmt_gaji = $conn->prepare(
            "SELECT g.*, k.Nama_Karyawan, j.Nama_Jabatan, dg.Nominal_Gapok 
             FROM GAJI g 
             JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
             JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
             LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
             WHERE g.Id_Karyawan = ? AND (g.Status = 'Dibayarkan' OR g.Status = 'Disetujui')
             ORDER BY g.Tgl_Gaji DESC
             LIMIT 1"
        );
        $stmt_gaji->bind_param("s", $id_karyawan_login);
        $stmt_gaji->execute();
        $slip_data = $stmt_gaji->get_result()->fetch_assoc();
        $stmt_gaji->close();
    }
}
$conn->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Slip Gaji</h2>
            <p class="text-gray-500 text-sm">Rincian pendapatan dan potongan gaji Anda.</p>
        </div>
        <?php if ($slip_data): // Hanya tampilkan tombol jika ada data ?>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i>Cetak
            </button>
            <a href="cetak_slip_gaji_pdf.php?id=<?= e($slip_data['Id_Gaji']) ?>" target="_blank" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i>Unduh PDF
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($slip_data): ?>
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-200">
            <div class="text-center mb-8 pb-6 border-b-2 border-dashed">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 font-poppins">SLIP GAJI KARYAWAN</h1>
                <p class="text-gray-600">Periode: <?= e(date('F Y', strtotime($slip_data['Tgl_Gaji']))) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 mb-8 text-sm">
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Nama Karyawan:</span><span class="font-semibold text-gray-800"><?= e($slip_data['Nama_Karyawan']) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Jabatan:</span><span class="font-semibold text-gray-800"><?= e($slip_data['Nama_Jabatan']) ?></span></div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">ID Karyawan:</span><span class="font-semibold text-gray-800"><?= e($id_karyawan_login) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Tanggal Pembayaran:</span><span class="font-semibold text-gray-800"><?= e(date('d M Y', strtotime($slip_data['Tgl_Gaji']))) ?></span></div>
                </div>
            </div>

            <hr class="my-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div>
                    <h3 class="text-lg font-bold text-green-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-down"></i>PENDAPATAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Gaji Pokok</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['Nominal_Gapok'] ?? 0, 2, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tunjangan</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['Total_Tunjangan'], 2, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Lembur</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['Total_Lembur'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Pendapatan (Gaji Kotor)</span>
                        <span>Rp <?= number_format($slip_data['Gaji_Kotor'], 2, ',', '.') ?></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-up"></i>POTONGAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                         <div class="flex justify-between">
                            <span class="text-gray-600">Total Potongan</span>
                            <span class="font-semibold text-red-600">- Rp <?= number_format($slip_data['Total_Potongan'], 2, ',', '.') ?></span>
                         </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Potongan</span>
                        <span class="text-red-600">- Rp <?= number_format($slip_data['Total_Potongan'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-10 bg-green-50 p-4 rounded-lg text-center sm:text-right">
                <p class="text-sm font-semibold text-gray-600">GAJI BERSIH (TAKE HOME PAY)</p>
                <p class="text-3xl font-bold text-green-800">Rp <?= number_format($slip_data['Gaji_Bersih'], 2, ',', '.') ?></p>
            </div>

        </div>
    <?php else: ?>
        <div class="bg-white p-10 rounded-xl shadow-lg text-center border border-gray-200">
            <i class="fa-solid fa-folder-open text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-700">Belum Ada Data</h3>
            <p class="text-gray-500 mt-2">Slip gaji Anda akan tersedia di sini setelah proses penggajian selesai dan disetujui/dibayarkan.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php
$page_title = 'Slip Gaji';
$current_page = 'slip_gaji';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('karyawan');

$conn = db_connect();
$slip_data = null;
$presensi_data = null;
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
            "SELECT g.*, k.Nama_Karyawan, k.Tgl_Awal_Kerja, j.Nama_Jabatan, gp.Nominal as Gaji_Pokok 
             FROM GAJI g 
             JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
             JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
             LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
             LEFT JOIN GAJI_POKOK gp ON dg.Id_Gapok = gp.Id_Gapok
             WHERE g.Id_Karyawan = ? AND (g.Status = 'Dibayarkan' OR g.Status = 'Disetujui')
             ORDER BY g.Tgl_Gaji DESC
             LIMIT 1"
        );
        $stmt_gaji->bind_param("s", $id_karyawan_login);
        $stmt_gaji->execute();
        $slip_data = $stmt_gaji->get_result()->fetch_assoc();
        $stmt_gaji->close();

        // Ambil data presensi untuk periode gaji terkait
        if ($slip_data) {
            $bulan_nama_db = date('F', strtotime($slip_data['Tgl_Gaji']));
            $bulan_map = [ "January" => "Januari", "February" => "Februari", "March" => "Maret", "April" => "April", "May" => "Mei", "June" => "Juni", "July" => "Juli", "August" => "Agustus", "September" => "September", "October" => "Oktober", "November" => "November", "December" => "Desember" ];
            $bulan_gaji = $bulan_map[$bulan_nama_db];
            $tahun_gaji = date('Y', strtotime($slip_data['Tgl_Gaji']));

            $stmt_presensi = $conn->prepare("SELECT Hadir, Sakit, Izin, Alpha, Jam_Lembur, Uang_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
            $stmt_presensi->bind_param("ssi", $id_karyawan_login, $bulan_gaji, $tahun_gaji);
            $stmt_presensi->execute();
            $presensi_data = $stmt_presensi->get_result()->fetch_assoc();
            $stmt_presensi->close();
            
            // Calculate overtime pay if not set
            if ($presensi_data && ($presensi_data['Uang_Lembur'] == 0 || $presensi_data['Uang_Lembur'] === null) && $presensi_data['Jam_Lembur'] > 0) {
                $presensi_data['Uang_Lembur'] = $presensi_data['Jam_Lembur'] * 20000;
            }
            
            // Set overtime pay variable with null handling - prioritize GAJI table data
            $uang_lembur = $slip_data['Total_Lembur'] ?? 0;
            if ($uang_lembur == 0 && $presensi_data && $presensi_data['Jam_Lembur'] > 0) {
                // Fallback to PRESENSI calculation if GAJI table doesn't have overtime data
                $uang_lembur = $presensi_data['Uang_Lembur'] ?? ($presensi_data['Jam_Lembur'] * 20000);
            }
        }
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
        <?php if ($slip_data): ?>
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
        <div class="bg-white shadow-lg print:shadow-none max-w-4xl mx-auto rounded-lg overflow-hidden">
            <!-- Main Salary Slip Table -->
            <table class="w-full border border-gray-300 table-fixed" style="border-collapse: collapse;">
                <!-- Company Header -->
                <tr>
                    <td colspan="2" class="border-b border-gray-300 p-8 text-center bg-white">
                        <h1 class="text-3xl font-bold text-green-700 mb-2">CV. KARYA WAHANA SENTOSA</h1>
                        <p class="text-gray-600 text-base mb-6">Jl. Imogiri Barat, Km.17, Bungas, Jetis, Bantul</p>
                        
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">SLIP GAJI KARYAWAN</h2>
                        <p class="text-gray-600 text-base">Periode : <?= e(date('F Y', strtotime($slip_data['Tgl_Gaji']))) ?></p>
                    </td>
                </tr>
                
                <!-- Employee Details Row -->
                <tr>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-6 w-1/2 bg-gray-50">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Nama Karyawan :</span>
                                <span class="font-bold text-gray-800"><?= e($slip_data['Nama_Karyawan']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Jabatan :</span>
                                <span class="font-bold text-gray-800"><?= e($slip_data['Nama_Jabatan']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="border-b border-gray-300 p-6 w-1/2 bg-white">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">ID Karyawan :</span>
                                <span class="font-bold text-gray-800"><?= e($id_karyawan_login) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Tanggal Pembayaran :</span>
                                <span class="font-bold text-gray-800"><?= e(date('d F Y', strtotime($slip_data['Tgl_Gaji']))) ?></span>
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- PENDAPATAN Section Header -->
                <tr>
                    <td colspan="2" class="border-b border-gray-300 p-4 bg-gradient-to-r from-green-100 to-emerald-100">
                        <h3 class="text-lg font-bold text-green-700">PENDAPATAN</h3>
                    </td>
                </tr>
                
                <!-- PENDAPATAN Items -->
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 w-2/3">
                        <span class="text-gray-700 font-medium">Gaji Pokok</span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 text-right w-1/3">
                        <span class="font-bold text-gray-800 text-base">Rp <?= number_format($slip_data['Gaji_Pokok'] ?? 0, 0, ',', '.') ?>,00</span>
                    </td>
                </tr>
                
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 w-2/3">
                        <span class="text-gray-700 font-medium">Tunjangan</span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 text-right w-1/3">
                        <span class="font-bold text-gray-800 text-base">Rp <?= number_format($slip_data['Total_Tunjangan'], 0, ',', '.') ?>,00</span>
                    </td>
                </tr>
                
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 w-2/3">
                        <span class="text-gray-700 font-medium">Lembur <span class="text-gray-500 text-sm">(<?= e($presensi_data['Jam_Lembur'] ?? 0) ?> Jam)</span></span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 text-right w-1/3">
                        <span class="font-bold text-gray-800 text-base">Rp <?= number_format($uang_lembur, 0, ',', '.') ?>,00</span>
                    </td>
                </tr>
                
                <!-- Total PENDAPATAN -->
                <tr>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 bg-green-50 w-2/3">
                        <span class="font-bold text-green-700 text-base">Total Pendapatan</span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 bg-green-50 text-right w-1/3">
                        <span class="font-bold text-green-700 text-lg">Rp <?= number_format($slip_data['Gaji_Kotor'], 0, ',', '.') ?>,00</span>
                    </td>
                </tr>

                <!-- POTONGAN Section Header -->
                <tr>
                    <td colspan="2" class="border-b border-gray-300 p-4 bg-gradient-to-r from-red-100 to-pink-100">
                        <h3 class="text-lg font-bold text-red-800">POTONGAN</h3>
                    </td>
                </tr>
                
                <!-- POTONGAN Items -->
                <?php
                $gaji_pokok = $slip_data['Gaji_Pokok'] ?? 0;
                $detail_potongan_display = [];
                
                // Potongan BPJS Ketenagakerjaan (2%)
                $potongan_bpjs = $gaji_pokok * 0.02;
                if ($potongan_bpjs > 0) {
                    $detail_potongan_display[] = ['nama' => 'Potongan BPJS Ketenagakerjaan', 'keterangan' => '(2%)', 'jumlah' => $potongan_bpjs];
                }
                
                // Potongan Absensi
                $total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
                if ($total_hari_tidak_hadir > 0) {
                    $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
                    $detail_potongan_display[] = ['nama' => "Potongan Absensi", 'keterangan' => "({$total_hari_tidak_hadir} Hari)", 'jumlah' => $potongan_absensi];
                }
                
                if (!empty($detail_potongan_display)):
                    foreach ($detail_potongan_display as $potongan):
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 w-2/3">
                        <div class="font-medium text-gray-700"><?= e($potongan['nama']) ?></div>
                        <div class="text-sm text-gray-500 mt-1"><?= e($potongan['keterangan']) ?></div>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 text-right w-1/3">
                        <span class="font-bold text-red-600 text-base">Rp <?= number_format($potongan['jumlah'], 0, ',', '.') ?>,00</span>
                    </td>
                </tr>
                <?php 
                    endforeach;
                else:
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 w-2/3">
                        <span class="text-gray-700 font-medium">Tidak ada potongan</span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 text-right w-1/3">
                        <span class="font-bold text-gray-800 text-base">Rp 0,00</span>
                    </td>
                </tr>
                <?php endif; ?>
                
                <!-- Total POTONGAN -->
                <tr>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 bg-red-50 w-2/3">
                        <span class="font-bold text-red-800 text-base">Total Potongan</span>
                    </td>
                    <td class="border-r border-gray-300 border-b border-gray-300 p-4 bg-red-50 text-right w-1/3">
                        <span class="font-bold text-red-800 text-lg">Rp <?= number_format($slip_data['Total_Potongan'], 0, ',', '.') ?>,00</span>
                    </td>
                </tr>

                <!-- RINCIAN KEHADIRAN Section -->
                <tr>
                    <td colspan="2" class="border-b border-gray-300 p-4 bg-gradient-to-r from-blue-100 to-indigo-100">
                        <h3 class="text-lg font-bold text-blue-800">RINCIAN KEHADIRAN</h3>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2" class="border-b border-gray-300 p-6 text-center bg-white">
                        <div class="flex justify-around items-center">
                            <div class="text-center">
                                <div class="font-bold text-black text-2xl mb-1"><?= e($presensi_data['Hadir'] ?? 0) ?></div>
                                <div class="text-gray-600 text-sm">Jumlah Hadir</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-black text-2xl mb-1"><?= e($presensi_data['Sakit'] ?? 0) ?></div>
                                <div class="text-gray-600 text-sm">Jumlah Sakit</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-black text-2xl mb-1"><?= e($presensi_data['Izin'] ?? 0) ?></div>
                                <div class="text-gray-600 text-sm">Jumlah Izin</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-black text-2xl mb-1"><?= e($presensi_data['Alpha'] ?? 0) ?></div>
                                <div class="text-gray-600 text-sm">Jumlah Alpha</div>
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- GAJI BERSIH DITERIMA Section -->
                <tr>
                    <td colspan="2" class="border border-gray-300 p-6 text-center bg-gray-50">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-800 text-xl">GAJI BERSIH DITERIMA (TAKE HOME PAY)</span>
                            <span class="font-bold text-green-700 text-2xl">Rp <?= number_format($slip_data['Gaji_Bersih'], 0, ',', '.') ?>,00</span>
                        </div>
                    </td>
                </tr>
            </table>
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

<style>
@media print {
    .no-print { display: none !important; }
    body { 
        font-family: Arial, sans-serif; 
        color: black !important;
        background: white !important;
        font-size: 12px;
    }
    table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    .border-gray-300 { border-color: #999 !important; }
    .text-gray-700, .text-gray-800 { color: #333 !important; }
    .text-gray-600 { color: #666 !important; }
    .text-green-700 { color: #15803d !important; }
    .text-red-800 { color: #991b1b !important; }
    .text-blue-800 { color: #1e40af !important; }
    .text-green-600 { color: #16a34a !important; }
    .text-red-600 { color: #dc2626 !important; }
    .text-yellow-600 { color: #ca8a04 !important; }
    .text-blue-600 { color: #2563eb !important; }
    .bg-gradient-to-r { background: linear-gradient(to right, var(--tw-gradient-stops)) !important; }
    .from-green-50 { --tw-gradient-from: #f0fdf4 !important; }
    .to-emerald-50 { --tw-gradient-to: #ecfdf5 !important; }
    .from-green-100 { --tw-gradient-from: #dcfce7 !important; }
    .to-emerald-100 { --tw-gradient-to: #d1fae5 !important; }
    .from-red-100 { --tw-gradient-from: #fee2e2 !important; }
    .to-pink-100 { --tw-gradient-to: #fce7f3 !important; }
    .from-blue-100 { --tw-gradient-from: #dbeafe !important; }
    .to-indigo-100 { --tw-gradient-to: #e0e7ff !important; }
    .bg-gray-50, .bg-green-50, .bg-red-50 { background-color: #f9f9f9 !important; }
    .font-bold { font-weight: bold !important; }
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .rounded-lg { border-radius: 8px !important; }
    .shadow-lg { box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1) !important; }
}

.hover\:bg-gray-50:hover {
    background-color: #f9fafb;
}

.transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
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
        <div class="bg-white rounded-xl shadow-lg border-2 border-gray-300 overflow-hidden print:shadow-none print:border-2 print:border-black">
            <!-- Outer border table structure -->
            <table class="w-full border-collapse">
                <!-- Company Header -->
                <tr>
                    <td class="border border-gray-400 p-6 text-center bg-gradient-to-r from-blue-50 to-indigo-50 print:bg-white">
                        <div class="space-y-2">
                            <h1 class="text-2xl font-bold text-gray-800 font-poppins">CV. KARYA WAHANA SENTOSA</h1>
                            <p class="text-gray-600 text-sm">Jl. Imogiri Barat, Km.17, Bungas, Jetis, Bantul</p>
                            <div class="mt-4 pt-4 border-t border-gray-300">
                                <h2 class="text-xl font-bold text-gray-800">SLIP GAJI KARYAWAN</h2>
                                <p class="text-gray-600 text-sm mt-1">Periode : <?= e(date('F Y', strtotime($slip_data['Tgl_Gaji']))) ?></p>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- Employee Details -->
                <tr>
                    <td class="border border-gray-400 p-0">
                        <table class="w-full">
                            <tr>
                                <td class="border-r border-gray-400 p-4 w-1/2 bg-gray-50">
                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center">
                                            <span class="w-32 text-gray-600 font-medium">Nama Karyawan :</span>
                                            <span class="font-bold text-gray-800"><?= e($slip_data['Nama_Karyawan']) ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-32 text-gray-600 font-medium">Jabatan :</span>
                                            <span class="font-bold text-gray-800"><?= e($slip_data['Nama_Jabatan']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 w-1/2 bg-white">
                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center">
                                            <span class="w-32 text-gray-600 font-medium">ID Karyawan :</span>
                                            <span class="font-bold text-gray-800 font-mono"><?= e($id_karyawan_login) ?></span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-32 text-gray-600 font-medium">Tanggal Pembayaran :</span>
                                            <span class="font-bold text-gray-800"><?= e(date('d F Y', strtotime($slip_data['Tgl_Gaji']))) ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- PENDAPATAN Section -->
                <tr>
                    <td class="border border-gray-400 p-0">
                        <table class="w-full">
                            <tr>
                                <td class="bg-gradient-to-r from-green-100 to-emerald-100 p-4 border-b border-gray-400 print:bg-green-100">
                                    <h3 class="text-lg font-bold text-green-800 flex items-center gap-2">
                                        <span class="text-green-600 text-xl">💰</span> PENDAPATAN
                                    </h3>
                                </td>
                            </tr>
                        </table>
                        <table class="w-full text-sm">
                            <tr class="bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="border-r border-gray-400 p-4 font-medium text-gray-700">Gaji Pokok</td>
                                <td class="p-4 text-right font-bold text-gray-900 text-base">Rp <?= number_format($slip_data['Gaji_Pokok'] ?? 0, 0, ',', '.') ?></td>
                            </tr>
                            <tr class="bg-white hover:bg-gray-50 transition-colors">
                                <td class="border-r border-gray-400 p-4 font-medium text-gray-700">Tunjangan</td>
                                <td class="p-4 text-right font-bold text-gray-900 text-base">Rp <?= number_format($slip_data['Total_Tunjangan'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="border-r border-gray-400 p-4 font-medium text-gray-700">Lembur <span class="text-gray-500 text-xs">(<?= e($presensi_data['Jam_Lembur'] ?? 0) ?> Jam @ Rp 20.000)</span></td>
                                <td class="p-4 text-right font-bold text-gray-900 text-base">Rp <?= number_format($uang_lembur, 0, ',', '.') ?></td>
                            </tr>
                            <tr class="border-t-2 border-green-500 bg-green-50">
                                <td class="border-r border-gray-400 p-4 font-bold text-green-800 text-base">Total Pendapatan</td>
                                <td class="p-4 text-right font-bold text-green-800 text-lg">Rp <?= number_format($slip_data['Gaji_Kotor'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- POTONGAN Section -->
                <tr>
                    <td class="border border-gray-400 p-0">
                        <table class="w-full">
                            <tr>
                                <td class="bg-gradient-to-r from-red-100 to-pink-100 p-4 border-b border-gray-400 print:bg-red-100">
                                    <h3 class="text-lg font-bold text-red-800 flex items-center gap-2">
                                        <span class="text-red-600 text-xl">📉</span> POTONGAN
                                    </h3>
                                </td>
                            </tr>
                        </table>
                        <table class="w-full text-sm">
                            <?php
                            $gaji_pokok = $slip_data['Gaji_Pokok'] ?? 0;
                            $detail_potongan_display = [];
                            
                            // Potongan BPJS Ketenagakerjaan (2%)
                            $potongan_bpjs = $gaji_pokok * 0.02;
                            if ($potongan_bpjs > 0) {
                                $detail_potongan_display[] = ['nama' => 'BPJS Ketenagakerjaan', 'keterangan' => '(2% dari gaji pokok)', 'jumlah' => $potongan_bpjs];
                            }
                            
                            // Potongan Absensi
                            $total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
                            if ($total_hari_tidak_hadir > 0) {
                                $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
                                $detail_potongan_display[] = ['nama' => 'Potongan Absensi', 'keterangan' => "({$total_hari_tidak_hadir} hari tidak hadir)", 'jumlah' => $potongan_absensi];
                            }
                            
                            if (!empty($detail_potongan_display)):
                                $row_count = 0;
                                foreach ($detail_potongan_display as $potongan):
                                    $bg_class = ($row_count % 2 == 0) ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white hover:bg-gray-50';
                            ?>
                            <tr class="<?= $bg_class ?> transition-colors">
                                <td class="border-r border-gray-400 p-4">
                                    <div class="font-medium text-gray-700"><?= e($potongan['nama']) ?></div>
                                    <div class="text-xs text-gray-500 mt-1"><?= e($potongan['keterangan']) ?></div>
                                </td>
                                <td class="p-4 text-right font-bold text-red-600 text-base">-Rp <?= number_format($potongan['jumlah'], 0, ',', '.') ?></td>
                            </tr>
                            <?php 
                                    $row_count++;
                                endforeach;
                            else:
                            ?>
                            <tr class="bg-gray-50">
                                <td class="border-r border-gray-400 p-4 text-gray-500 font-medium">Tidak ada potongan</td>
                                <td class="p-4 text-right font-bold text-gray-900 text-base">Rp 0</td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-t-2 border-red-500 bg-red-50">
                                <td class="border-r border-gray-400 p-4 font-bold text-red-800 text-base">Total Potongan</td>
                                <td class="p-4 text-right font-bold text-red-800 text-lg">-Rp <?= number_format($slip_data['Total_Potongan'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- RINCIAN KEHADIRAN Section -->
                <tr>
                    <td class="border border-gray-400 p-0">
                        <table class="w-full">
                            <tr>
                                <td class="bg-gradient-to-r from-blue-100 to-indigo-100 p-4 border-b border-gray-400 print:bg-blue-100">
                                    <h4 class="text-lg font-bold text-blue-800 flex items-center gap-2">
                                        <span class="text-blue-600 text-xl">📅</span> RINCIAN KEHADIRAN
                                    </h4>
                                </td>
                            </tr>
                        </table>
                        <table class="w-full text-sm">
                            <tr class="bg-gray-50">
                                <td class="border-r border-gray-400 p-4 text-center w-1/4 hover:bg-gray-100 transition-colors">
                                    <div class="space-y-2">
                                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mx-auto text-white text-xs font-bold">✓</div>
                                        <div class="font-bold text-green-600 text-xl"><?= e($presensi_data['Hadir'] ?? 0) ?></div>
                                        <div class="text-gray-600 text-xs font-medium">Hari Hadir</div>
                                    </div>
                                </td>
                                <td class="border-r border-gray-400 p-4 text-center w-1/4 hover:bg-gray-100 transition-colors">
                                    <div class="space-y-2">
                                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mx-auto text-white text-xs font-bold">🤒</div>
                                        <div class="font-bold text-yellow-600 text-xl"><?= e($presensi_data['Sakit'] ?? 0) ?></div>
                                        <div class="text-gray-600 text-xs font-medium">Hari Sakit</div>
                                    </div>
                                </td>
                                <td class="border-r border-gray-400 p-4 text-center w-1/4 hover:bg-gray-100 transition-colors">
                                    <div class="space-y-2">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mx-auto text-white text-xs font-bold">🕋</div>
                                        <div class="font-bold text-blue-600 text-xl"><?= e($presensi_data['Izin'] ?? 0) ?></div>
                                        <div class="text-gray-600 text-xs font-medium">Hari Izin</div>
                                    </div>
                                </td>
                                <td class="p-4 text-center w-1/4 hover:bg-gray-100 transition-colors">
                                    <div class="space-y-2">
                                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center mx-auto text-white text-xs font-bold">✕</div>
                                        <div class="font-bold text-red-600 text-xl"><?= e($presensi_data['Alpha'] ?? 0) ?></div>
                                        <div class="text-gray-600 text-xs font-medium">Hari Alpha</div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- GAJI BERSIH Section -->
                <tr>
                    <td class="border border-gray-400 p-0">
                        <table class="w-full bg-gray-50">
                            <tr>
                                <td class="p-8 text-center">
                                    <div class="space-y-3">
                                        <h3 class="text-lg font-bold text-gray-700 uppercase tracking-wide">Gaji Bersih Diterima</h3>
                                        <p class="text-sm text-gray-600">(Take Home Pay)</p>
                                        <div class="text-4xl font-bold text-gray-800 mt-4">Rp <?= number_format($slip_data['Gaji_Bersih'], 0, ',', '.') ?></div>
                                    </div>
                                </td>
                            </tr>
                        </table>
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
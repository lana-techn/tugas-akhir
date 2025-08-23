<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('pemilik'); // Memastikan hanya pemilik yang bisa akses

$conn = db_connect();
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    set_flash_message('error', 'ID Gaji tidak ditemukan.');
    header('Location: penggajian_pemilik.php');
    exit;
}

// 1. Ambil data ringkasan dari tabel GAJI dan data karyawan
$stmt_gaji = $conn->prepare(
    "SELECT g.*, k.Nama_Karyawan, k.Id_Karyawan, j.Nama_Jabatan, k.Tgl_Awal_Kerja 
     FROM GAJI g 
     JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
     JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan 
     WHERE g.Id_Gaji = ?"
);
$stmt_gaji->bind_param("s", $id_gaji);
$stmt_gaji->execute();
$gaji_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$gaji_data) {
    set_flash_message('error', 'Data gaji dengan ID tersebut tidak ditemukan.');
    header('Location: penggajian_pemilik.php');
    exit;
}

// 2. Ambil data rincian dari tabel DETAIL_GAJI dengan JOIN ke GAJI_POKOK
$stmt_detail = $conn->prepare(
    "SELECT dg.*, gp.Nominal as Gaji_Pokok 
     FROM DETAIL_GAJI dg 
     JOIN GAJI_POKOK gp ON dg.Id_Gapok = gp.Id_Gapok 
     WHERE dg.Id_Gaji = ?"
);
$stmt_detail->bind_param("s", $id_gaji);
$stmt_detail->execute();
$detail_data = $stmt_detail->get_result()->fetch_assoc();
$stmt_detail->close();

// 3. Ambil data dari tabel PRESENSI untuk rincian kehadiran dan potongan
$bulan_nama_db = date('F', strtotime($gaji_data['Tgl_Gaji']));
$bulan_map = [ "January" => "Januari", "February" => "Februari", "March" => "Maret", "April" => "April", "May" => "Mei", "June" => "Juni", "July" => "Juli", "August" => "Agustus", "September" => "September", "October" => "Oktober", "November" => "November", "December" => "Desember" ];
$bulan_gaji = $bulan_map[$bulan_nama_db];
$tahun_gaji = date('Y', strtotime($gaji_data['Tgl_Gaji']));

$stmt_presensi = $conn->prepare("SELECT Hadir, Sakit, Izin, Alpha, Jam_Lembur, Uang_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
$stmt_presensi->bind_param("ssi", $gaji_data['Id_Karyawan'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();


// 4. Hitung ulang komponen untuk ditampilkan di detail
$gaji_pokok = $detail_data['Gaji_Pokok'] ?? 0;
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;

// Handle legacy overtime data - if Uang_Lembur is 0 but Jam_Lembur exists, calculate it
$uang_lembur = $presensi_data['Uang_Lembur'] ?? 0;
if ($uang_lembur == 0 && $jam_lembur > 0) {
    $uang_lembur = $jam_lembur * 20000; // Use default rate of 20,000 per hour
}

// Rincian Potongan
$detail_potongan_display = [];
$potongan_bpjs = $gaji_pokok * 0.02;
if ($potongan_bpjs > 0) {
    $detail_potongan_display[] = ['nama' => 'Potongan BPJS Ketenagakerjaan (2%)', 'jumlah' => $potongan_bpjs];
}
$total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
if ($total_hari_tidak_hadir > 0) {
    $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
    $detail_potongan_display[] = ['nama' => "Potongan Absensi ({$total_hari_tidak_hadir} hari)", 'jumlah' => $potongan_absensi];
}

// Hitung masa kerja untuk ditampilkan
$tgl_awal_kerja = new DateTime($gaji_data['Tgl_Awal_Kerja']);
$tgl_gaji = new DateTime($gaji_data['Tgl_Gaji']);
$masa_kerja_text = $tgl_gaji->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');

$page_title = 'Detail Gaji: ' . e($gaji_data['Nama_Karyawan']);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-gradient-to-r from-indigo-50 to-blue-50 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border border-gray-200">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                    <i class="fas fa-file-invoice-dollar text-indigo-600 text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Detail Gaji Karyawan</h1>
                <p class="text-gray-600">Rincian perhitungan gaji untuk periode <?= date('F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></p>
            </div>
            
            <!-- Employee Information Card -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border-l-4 border-indigo-500">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle text-indigo-500"></i>
                    <span>Informasi Karyawan</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            <div>
                                <span class="text-sm text-gray-600 block">Nama Karyawan</span>
                                <span class="font-bold text-gray-800 text-lg"><?= e($gaji_data['Nama_Karyawan']) ?></span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                            <div>
                                <span class="text-sm text-gray-600 block">Jabatan</span>
                                <span class="font-bold text-gray-800"><?= e($gaji_data['Nama_Jabatan']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                            <div>
                                <span class="text-sm text-gray-600 block">Tanggal Gaji</span>
                                <span class="font-bold text-gray-800"><?= date('d F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                            <div>
                                <span class="text-sm text-gray-600 block">Masa Kerja</span>
                                <span class="font-bold text-gray-800"><?= e($masa_kerja_text) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Income Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <div class="bg-white/20 rounded-full p-2">
                            <i class="fas fa-arrow-up text-lg"></i>
                        </div>
                        <span>PENDAPATAN</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                            <span class="font-medium text-gray-700">Gaji Pokok</span>
                        </div>
                        <span class="font-bold text-lg text-gray-900">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-purple-400 rounded-full"></div>
                            <span class="font-medium text-gray-700">Tunjangan</span>
                        </div>
                        <span class="font-bold text-lg text-gray-900">Rp <?= number_format($gaji_data['Total_Tunjangan'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-orange-400 rounded-full"></div>
                            <span class="font-medium text-gray-700">Lembur <span class="text-sm text-gray-500">(<?= e($jam_lembur) ?> jam)</span></span>
                        </div>
                        <span class="font-bold text-lg text-gray-900">Rp <?= number_format($uang_lembur, 0, ',', '.') ?></span>
                    </div>
                    <div class="border-t-2 border-green-200 pt-4">
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg">
                            <span class="font-bold text-lg">Total Pendapatan</span>
                            <span class="font-bold text-xl">Rp <?= number_format($gaji_data['Gaji_Kotor'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deductions Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6 text-white">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <div class="bg-white/20 rounded-full p-2">
                            <i class="fas fa-arrow-down text-lg"></i>
                        </div>
                        <span>POTONGAN</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <?php if(!empty($detail_potongan_display)): ?>
                        <?php foreach($detail_potongan_display as $p): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                <span class="font-medium text-gray-700"><?= e($p['nama']) ?></span>
                            </div>
                            <span class="font-bold text-lg text-red-600">-Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                <span class="font-medium text-gray-500">Tidak ada potongan</span>
                            </div>
                            <span class="font-bold text-lg text-gray-900">Rp 0</span>
                        </div>
                    <?php endif; ?>
                    <div class="border-t-2 border-red-200 pt-4">
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg">
                            <span class="font-bold text-lg">Total Potongan</span>
                            <span class="font-bold text-xl">-Rp <?= number_format($gaji_data['Total_Potongan'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mt-8">
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6 text-white">
                <h3 class="text-xl font-bold flex items-center gap-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <i class="fas fa-calendar-check text-lg"></i>
                    </div>
                    <span>RINCIAN KEHADIRAN</span>
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="bg-green-500 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-white">
                            <i class="fas fa-check text-lg"></i>
                        </div>
                        <div class="text-3xl font-bold text-green-600 mb-1"><?= e($presensi_data['Hadir'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600 font-medium">Hari Hadir</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <div class="bg-yellow-500 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-white">
                            <i class="fas fa-thermometer-half text-lg"></i>
                        </div>
                        <div class="text-3xl font-bold text-yellow-600 mb-1"><?= e($presensi_data['Sakit'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600 font-medium">Hari Sakit</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="bg-blue-500 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-white">
                            <i class="fas fa-hand-paper text-lg"></i>
                        </div>
                        <div class="text-3xl font-bold text-blue-600 mb-1"><?= e($presensi_data['Izin'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600 font-medium">Hari Izin</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
                        <div class="bg-red-500 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 text-white">
                            <i class="fas fa-times text-lg"></i>
                        </div>
                        <div class="text-3xl font-bold text-red-600 mb-1"><?= e($presensi_data['Alpha'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600 font-medium">Hari Alpha</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Amount Section -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl p-8 text-white text-center shadow-xl mt-8">
            <div class="mb-4">
                <div class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
            <div class="space-y-2">
                <h3 class="text-2xl font-bold uppercase tracking-wide">Gaji Bersih Diterima</h3>
                <p class="text-lg opacity-90">(Take Home Pay)</p>
                <div class="text-5xl font-bold mt-6">Rp <?= number_format($gaji_data['Gaji_Bersih'], 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="flex items-center justify-center pt-8 pb-4">
            <a href="penggajian_pemilik.php" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl text-gray-600 bg-white hover:bg-gray-50 font-semibold text-lg transition-all shadow-lg hover:shadow-xl border border-gray-200">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali ke Daftar Persetujuan</span>
            </a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
<?php
require_once __DIR__ . '/../includes/functions.php';
// Memeriksa login untuk admin atau pemilik
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['level'], ['Admin', 'Pemilik'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}


$conn = db_connect();
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    set_flash_message('error', 'ID Gaji tidak ditemukan.');
    header('Location: pengajuan_gaji.php');
    exit;
}

// 1. Ambil data ringkasan dari tabel GAJI dan data karyawan
$stmt_gaji = $conn->prepare(
    "SELECT g.*, k.Nama_Karyawan, j.Nama_Jabatan, k.Tgl_Awal_Kerja 
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
    header('Location: pengajuan_gaji.php');
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

// Calculate overtime pay if not set
if ($presensi_data && ($presensi_data['Uang_Lembur'] == 0 || $presensi_data['Uang_Lembur'] === null) && $presensi_data['Jam_Lembur'] > 0) {
    $presensi_data['Uang_Lembur'] = $presensi_data['Jam_Lembur'] * 20000;
}
$conn->close();


// 4. Hitung ulang komponen untuk ditampilkan di detail
$gaji_pokok = $detail_data['Gaji_Pokok'] ?? 0;
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;
$uang_lembur = $presensi_data['Uang_Lembur'] ?? 0;

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
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-white p-8 rounded-xl shadow-lg max-w-3xl mx-auto border border-gray-200">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold font-poppins text-gray-800">DETAIL GAJI</h2>
        <p class="text-gray-500 mt-1">Rincian perhitungan gaji untuk periode <?= date('F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></p>
    </div>
    
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Nama Karyawan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['Nama_Karyawan']) ?></span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Jabatan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['Nama_Jabatan']) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Tanggal Gaji</span><span class="text-sm font-semibold text-gray-800"><?= date('d F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Masa Kerja</span><span class="text-sm font-semibold text-gray-800"><?= e($masa_kerja_text) ?></span></div>
        </div>

        <div class="space-y-4">
            <div>
                <h3 class="font-bold text-lg text-green-700 mb-2">PENDAPATAN</h3>
                <div class="border rounded-md">
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Gaji Pokok</span><span class="font-semibold">Rp <?= number_format($gaji_pokok, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Tunjangan</span><span class="font-semibold">Rp <?= number_format($gaji_data['Total_Tunjangan'], 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Lembur (<?= e($jam_lembur) ?> jam)</span><span class="font-semibold">Rp <?= number_format($uang_lembur, 2, ',', '.') ?></span></div>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span>Total Pendapatan (Gaji Kotor)</span><span>Rp <?= number_format($gaji_data['Gaji_Kotor'], 2, ',', '.') ?></span></div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-red-700 mb-2">POTONGAN</h3>
                <div class="border rounded-md">
                    <?php if(!empty($detail_potongan_display)): ?>
                        <?php foreach($detail_potongan_display as $p): ?>
                            <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm"><?= e($p['nama']) ?></span><span class="text-sm font-semibold text-red-600">- Rp <?= number_format($p['jumlah'], 2, ',', '.') ?></span></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex justify-between py-2.5 px-4"><span class="text-sm text-gray-500">Tidak ada potongan</span><span class="font-semibold text-red-600">- Rp 0</span></div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span class="text-red-600">Total Potongan</span><span class="text-red-600">- Rp <?= number_format($gaji_data['Total_Potongan'], 2, ',', '.') ?></span></div>
            </div>
            
            <div>
                <h3 class="font-bold text-lg text-blue-700 mb-2">RINCIAN KEHADIRAN</h3>
                <div class="border rounded-md divide-y">
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Hadir</span><span class="font-semibold"><?= e($presensi_data['Hadir'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Sakit</span><span class="font-semibold"><?= e($presensi_data['Sakit'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Izin</span><span class="font-semibold"><?= e($presensi_data['Izin'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Alpha</span><span class="font-semibold"><?= e($presensi_data['Alpha'] ?? 0) ?> hari</span></div>
                </div>
            </div>
        </div>

        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-lg flex justify-between items-center mt-6">
            <span class="text-lg font-bold font-poppins">GAJI BERSIH (TAKE HOME PAY)</span>
            <span class="text-xl font-bold">Rp <?= number_format($gaji_data['Gaji_Bersih'], 2, ',', '.') ?></span>
        </div>

        <div class="flex items-center justify-end pt-6">
            <a href="pengajuan_gaji.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali ke Daftar Gaji</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
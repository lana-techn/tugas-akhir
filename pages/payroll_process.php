<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();

// BAGIAN 1: PROSES PENGAJUAN FINAL (SAAT TOMBOL 'AJUKAN' DITEKAN DARI HALAMAN DETAIL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_gaji'])) {

    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    // Ambil semua data yang sudah final dari hidden input
    $id_karyawan = $_POST['Id_Karyawan'] ?? null;
    $periode = $_POST['periode'] ?? null;
    $gaji_pokok = (float)($_POST['Gaji_Pokok'] ?? 0);
    $id_gapok = (int)($_POST['Id_Gapok'] ?? null);
    $total_tunjangan = (float)($_POST['Total_Tunjangan'] ?? 0);
    $total_lembur = (float)($_POST['Total_Lembur'] ?? 0);
    $total_potongan = (float)($_POST['Total_Potongan'] ?? 0);
    $gaji_kotor = (float)($_POST['Gaji_Kotor'] ?? 0);
    $gaji_bersih = (float)($_POST['Gaji_Bersih'] ?? 0);

    if (empty($id_karyawan) || empty($periode)) {
        set_flash_message('error', 'Data tidak lengkap. Gagal mengajukan gaji.');
        header('Location: pengajuan_gaji.php');
        exit;
    }

    $tgl_gaji = date('Y-m-t', strtotime($periode . '-01'));
    $periode_id = date('Ym', strtotime($tgl_gaji));
    $id_gaji = "G-" . $periode_id . "-" . $id_karyawan;

    // Cek duplikasi
    $stmt_cek = $conn->prepare("SELECT Id_Gaji FROM GAJI WHERE Id_Gaji = ?");
    $stmt_cek->bind_param("s", $id_gaji);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows > 0) {
        set_flash_message('error', "Gaji untuk karyawan ini pada periode " . date('F Y', strtotime($tgl_gaji)) . " sudah pernah diajukan.");
        header('Location: pengajuan_gaji.php');
        exit;
    }
    $stmt_cek->close();

    $conn->begin_transaction();
    try {
        // 1. INSERT ke tabel GAJI (Ringkasan)
        $stmt_gaji = $conn->prepare(
            "INSERT INTO GAJI (Id_Gaji, Id_Karyawan, Tgl_Gaji, Total_Tunjangan, Total_Lembur, Total_Potongan, Gaji_Kotor, Gaji_Bersih, Status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan')"
        );
        $stmt_gaji->bind_param("sssdddds", $id_gaji, $id_karyawan, $tgl_gaji, $total_tunjangan, $total_lembur, $total_potongan, $gaji_kotor, $gaji_bersih);
        $stmt_gaji->execute();

        // 2. INSERT ke tabel DETAIL_GAJI (Rincian)
        $stmt_detail = $conn->prepare(
            "INSERT INTO DETAIL_GAJI (Id_Gaji, Id_Karyawan, Id_Gapok, Nominal_Gapok, Jumlah_Tunjangan, Jumlah_Lembur, Jumlah_Potongan)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_detail->bind_param("ssiddid", $id_gaji, $id_karyawan, $id_gapok, $gaji_pokok, $total_tunjangan, $total_lembur, $total_potongan);
        $stmt_detail->execute();

        $conn->commit();
        set_flash_message('success', 'Pengajuan gaji berhasil dibuat dan menunggu persetujuan.');
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        set_flash_message('error', 'Gagal menyimpan data pengajuan gaji: ' . $exception->getMessage());
    } finally {
        if(isset($stmt_gaji)) $stmt_gaji->close();
        if(isset($stmt_detail)) $stmt_detail->close();
        $conn->close();
    }
    
    header('Location: pengajuan_gaji.php');
    exit;

} 
// BAGIAN 2: TAMPILAN DETAIL GAJI (KALKULASI OTOMATIS DARI FORM AWAL)
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id_Karyawan']) && isset($_POST['periode'])) {
    
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'];
    $periode = $_POST['periode'];
    $sertakan_tunjangan = (bool)($_POST['sertakan_tunjangan'] ?? false);

    $tgl_gaji = date('Y-m-t', strtotime($periode . '-01'));
    $tahun = date('Y', strtotime($tgl_gaji));
    $bulan_angka = (int)date('n', strtotime($tgl_gaji));
    $bulan_map = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $bulan_nama = $bulan_map[$bulan_angka];

    $stmt_karyawan = $conn->prepare("SELECT k.*, j.Nama_Jabatan FROM KARYAWAN k JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan WHERE k.Id_Karyawan = ?");
    $stmt_karyawan->bind_param("s", $id_karyawan);
    $stmt_karyawan->execute();
    $karyawan = $stmt_karyawan->get_result()->fetch_assoc();
    if (!$karyawan) die('Karyawan tidak ditemukan.');
    $stmt_karyawan->close();
    
    $tgl_awal_kerja = new DateTime($karyawan['Tgl_Awal_Kerja']);
    $masa_kerja_text = (new DateTime())->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');
    $masa_kerja_tahun = (new DateTime())->diff($tgl_awal_kerja)->y;

    $stmt_gapok = $conn->prepare("SELECT Id_Gapok, Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1");
    $stmt_gapok->bind_param("si", $karyawan['Id_Jabatan'], $masa_kerja_tahun);
    $stmt_gapok->execute();
    $gapok_data = $stmt_gapok->get_result()->fetch_assoc();
    $id_gapok = $gapok_data['Id_Gapok'] ?? null;
    $gaji_pokok = $gapok_data['Nominal'] ?? 0;
    $stmt_gapok->close();

    $all_tunjangan = [];
    $total_tunjangan = 0;
    if ($sertakan_tunjangan) {
        $thr = $gaji_pokok;
        $all_tunjangan[] = ['Nama_Tunjangan' => 'Tunjangan Hari Raya (THR)', 'Jumlah_Tunjangan' => $thr];
        $total_tunjangan += $thr;
    }

    $stmt_presensi = $conn->prepare("SELECT Jam_Lembur, Sakit, Izin, Alpha FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
    $stmt_presensi->bind_param("ssi", $id_karyawan, $bulan_nama, $tahun);
    $stmt_presensi->execute();
    $presensi_data = $stmt_presensi->get_result()->fetch_assoc();
    $stmt_presensi->close();

    $jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;
    $total_lembur = $jam_lembur * 20000;

    $detail_potongan = [];
    $total_potongan = 0;
    
    // PERBAIKAN: Mengubah perhitungan BPJS menjadi 2%
    $potongan_bpjs = $gaji_pokok * 0.02; 
    if($potongan_bpjs > 0) {
        $detail_potongan[] = ['nama' => 'Potongan BPJS Ketenagakerjaan (2%)', 'jumlah' => $potongan_bpjs];
        $total_potongan += $potongan_bpjs;
    }
    
    $total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
    if ($total_hari_tidak_hadir > 0) {
        $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
        $detail_potongan[] = ['nama' => "Potongan Absensi ({$total_hari_tidak_hadir} hari)", 'jumlah' => $potongan_absensi];
        $total_potongan += $potongan_absensi;
    }
    
    $gaji_kotor = $gaji_pokok + $total_tunjangan + $total_lembur;
    $gaji_bersih = $gaji_kotor - $total_potongan;
    
    generate_csrf_token();
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-3xl mx-auto border border-gray-200">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold font-poppins text-gray-800">DETAIL GAJI</h2>
            <p class="text-gray-500 mt-1">Harap periksa rincian gaji sebelum diajukan.</p>
        </div>
        
        <form method="POST" action="payroll_process.php" class="space-y-6">
            <?php csrf_input(); ?>
            <input type="hidden" name="ajukan_gaji" value="1">
            <input type="hidden" name="Id_Karyawan" value="<?= e($id_karyawan) ?>">
            <input type="hidden" name="periode" value="<?= e($periode) ?>"> 
            <input type="hidden" name="Id_Gapok" value="<?= e($id_gapok) ?>">
            <input type="hidden" name="Gaji_Pokok" value="<?= e($gaji_pokok) ?>">
            <input type="hidden" name="Total_Tunjangan" value="<?= e($total_tunjangan) ?>">
            <input type="hidden" name="Total_Lembur" value="<?= e($total_lembur) ?>">
            <input type="hidden" name="Total_Potongan" value="<?= e($total_potongan) ?>">
            <input type="hidden" name="Gaji_Kotor" value="<?= e($gaji_kotor) ?>">
            <input type="hidden" name="Gaji_Bersih" value="<?= e($gaji_bersih) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Nama Karyawan</span><span class="text-sm font-semibold text-gray-800"><?= e($karyawan['Nama_Karyawan']) ?></span></div>
                <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Jabatan</span><span class="text-sm font-semibold text-gray-800"><?= e($karyawan['Nama_Jabatan']) ?></span></div>
                <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Tanggal Gaji</span><span class="text-sm font-semibold text-gray-800"><?= date('d F Y', strtotime($tgl_gaji)) ?></span></div>
                <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Masa Kerja</span><span class="text-sm font-semibold text-gray-800"><?= e($masa_kerja_text) ?></span></div>
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="font-bold text-lg text-green-700 mb-2">PENDAPATAN</h3>
                    <div class="border rounded-md">
                        <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Gaji Pokok</span><span class="font-semibold">Rp <?= number_format($gaji_pokok, 2, ',', '.') ?></span></div>
                        <?php if(!empty($all_tunjangan)): foreach($all_tunjangan as $t): ?>
                        <div class="flex justify-between py-2.5 px-4 border-b bg-green-50"><span class="text-sm"><?= e($t['Nama_Tunjangan']) ?></span><span class="font-semibold">Rp <?= number_format($t['Jumlah_Tunjangan'], 2, ',', '.') ?></span></div>
                        <?php endforeach; else: ?>
                        <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm text-gray-500">Tidak ada tunjangan</span><span class="font-semibold">Rp 0</span></div>
                        <?php endif; ?>
                        <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Lembur (<?= e($jam_lembur) ?> jam)</span><span class="font-semibold">Rp <?= number_format($total_lembur, 2, ',', '.') ?></span></div>
                    </div>
                    <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span>Total Pendapatan (Gaji Kotor)</span><span>Rp <?= number_format($gaji_kotor, 2, ',', '.') ?></span></div>
                </div>

                <div>
                    <h3 class="font-bold text-lg text-red-700 mb-2">POTONGAN</h3>
                    <div class="border rounded-md">
                        <?php if(!empty($detail_potongan)): ?>
                            <?php foreach($detail_potongan as $p): ?>
                                <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm"><?= e($p['nama']) ?></span><span class="text-sm font-semibold text-red-600">- Rp <?= number_format($p['jumlah'], 2, ',', '.') ?></span></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="flex justify-between py-2.5 px-4"><span class="text-sm text-gray-500">Tidak ada potongan</span><span class="text-sm font-semibold text-red-600">- Rp 0</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span class="text-red-600">Total Potongan</span><span class="text-red-600">- Rp <?= number_format($total_potongan, 2, ',', '.') ?></span></div>
                </div>
            </div>

            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-lg flex justify-between items-center mt-6">
                <span class="text-lg font-bold font-poppins">GAJI BERSIH (TAKE HOME PAY)</span>
                <span class="text-xl font-bold">Rp <?= number_format($gaji_bersih, 2, ',', '.') ?></span>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="pengajuan_gaji.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-8 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    Ajukan Gaji
                </button>
            </div>
        </form>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
} else {
    // Jika halaman diakses langsung tanpa data POST, kembalikan ke form tambah
    header('Location: pengajuan_gaji.php?action=add');
    exit;
}
?>
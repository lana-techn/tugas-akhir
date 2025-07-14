<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();

// BAGIAN 1: PROSES PENGAJUAN FINAL (SAAT TOMBOL 'AJUKAN' DITEKAN)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_gaji'])) {

    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'] ?? null;
    $periode = $_POST['periode'] ?? null;

    if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        set_flash_message('error', 'Periode gaji tidak valid saat pengajuan. Proses dibatalkan.');
        header('Location: salary.php?action=new_payroll');
        exit;
    }
    
    $date = new DateTime($periode . '-01');
    $date->modify('last day of this month');
    $tgl_gaji = $date->format('Y-m-d');

    // Ambil data yang sudah dihitung dari form
    $gaji_pokok = $_POST['Gaji_Pokok'] ?? 0;
    $total_tunjangan = $_POST['Total_Tunjangan'] ?? 0;
    $total_lembur = $_POST['Total_Lembur'] ?? 0;
    $total_potongan = $_POST['Total_Potongan'] ?? 0;
    
    // PERBAIKAN LOGIKA PENGGAJIAN SESUAI RUMUS YANG BENAR:
    // Gaji Kotor = Gaji Pokok + Tunjangan + Lembur
    $gaji_kotor = $gaji_pokok + $total_tunjangan + $total_lembur;
    
    // Gaji Bersih = Gaji Kotor - Potongan
    $gaji_bersih = $gaji_kotor - $total_potongan;
    
    // Gaji (untuk db_penggajian) = Gaji Pokok + Tunjangan + Lembur - Potongan
    // Ini sama dengan Gaji Bersih, jadi kita gunakan $gaji_bersih
    $gaji = $gaji_bersih;

    $periode_id = date('Ym', strtotime($tgl_gaji));
    $id_gaji = "G-" . $periode_id . "-" . $id_karyawan;

    $stmt_cek = $conn->prepare("SELECT Id_Gaji FROM GAJI WHERE Id_Gaji = ?");
    $stmt_cek->bind_param("s", $id_gaji);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows > 0) {
        set_flash_message('error', "Gaji untuk karyawan ini pada periode " . date('F Y', strtotime($tgl_gaji)) . " sudah pernah diajukan.");
        header('Location: salary.php?action=new_payroll');
        exit;
    }
    $stmt_cek->close();

    // Insert ke database dengan struktur yang benar
    $stmt_insert = $conn->prepare(
        "INSERT INTO GAJI (Id_Gaji, Id_Karyawan, Tgl_Gaji, Gaji_Pokok, Total_Tunjangan, Total_Lembur, Total_Potongan, Gaji_Kotor, Gaji_Bersih, Gaji, Status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan')"
    );
    $stmt_insert->bind_param("sssddddddd", $id_gaji, $id_karyawan, $tgl_gaji, $gaji_pokok, $total_tunjangan, $total_lembur, $total_potongan, $gaji_kotor, $gaji_bersih, $gaji);
    
    if ($stmt_insert->execute()) {
        set_flash_message('success', 'Gaji berhasil diajukan dan menunggu persetujuan pemilik.');
    } else {
        set_flash_message('error', 'Gagal mengajukan gaji: ' . $stmt_insert->error);
    }
    $stmt_insert->close();
    header('Location: salary.php?action=list_gapok');
    exit;

} 

// BAGIAN 2: TAMPILAN DETAIL GAJI (SAAT DATA DITERIMA DARI FORM AWAL)

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id_Karyawan']) && isset($_POST['periode'])) {
    
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'];
    $periode = $_POST['periode'];
    
    if (empty($id_karyawan) || empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        set_flash_message('error', 'Karyawan atau Periode tidak valid. Silakan pilih kembali.');
        header('Location: salary.php?action=new_payroll');
        exit;
    }

    // --- MULAI KALKULASI OTOMATIS ---
    $tgl_gaji = date('Y-m-t', strtotime($periode)); 
    $bulan_nama = date('F', strtotime($tgl_gaji));
    $tahun = date('Y', strtotime($tgl_gaji));

    $stmt_karyawan = $conn->prepare("SELECT k.*, j.Nama_Jabatan FROM KARYAWAN k JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan WHERE k.Id_Karyawan = ?");
    $stmt_karyawan->bind_param("s", $id_karyawan);
    $stmt_karyawan->execute();
    $karyawan = $stmt_karyawan->get_result()->fetch_assoc();
    if (!$karyawan) die('Karyawan tidak ditemukan.');
    $stmt_karyawan->close();
    
    $tgl_awal_kerja = new DateTime($karyawan['Tgl_Awal_Kerja']);
    $masa_kerja_text = (new DateTime($tgl_gaji))->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');
    $masa_kerja_tahun = (new DateTime($tgl_gaji))->diff($tgl_awal_kerja)->y;

    // Ambil gaji pokok berdasarkan jabatan dan masa kerja
    $stmt_gapok = $conn->prepare("SELECT Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1");
    $stmt_gapok->bind_param("si", $karyawan['Id_Jabatan'], $masa_kerja_tahun);
    $stmt_gapok->execute();
    $gaji_pokok = $stmt_gapok->get_result()->fetch_assoc()['Nominal'] ?? 0;
    $stmt_gapok->close();

    // Mengambil semua jenis tunjangan dari tabel TUNJANGAN
    $all_tunjangan = $conn->query("SELECT Nama_Tunjangan, Jumlah_Tunjangan FROM TUNJANGAN")->fetch_all(MYSQLI_ASSOC);
    $total_tunjangan = array_sum(array_column($all_tunjangan, 'Jumlah_Tunjangan'));

    // PERBAIKAN: Ambil data lembur dari tabel PRESENSI berdasarkan karyawan dan periode
    $stmt_lembur = $conn->prepare("SELECT Jam_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
    $stmt_lembur->bind_param("ssi", $id_karyawan, $bulan_nama, $tahun);
    $stmt_lembur->execute();
    $jam_lembur = $stmt_lembur->get_result()->fetch_assoc()['Jam_Lembur'] ?? 0;
    $stmt_lembur->close();

    // Ambil tarif lembur tetap Rp 20.000 per jam
    $upah_per_jam_lembur = 20000; // Tarif tetap sesuai permintaan
    $total_lembur = $jam_lembur * $upah_per_jam_lembur;

    // PERBAIKAN LOGIKA PENGGAJIAN:
    // Gaji Kotor = Gaji Pokok + Tunjangan + Lembur
    $gaji_kotor = $gaji_pokok + $total_tunjangan + $total_lembur;

    // PERBAIKAN: Hitung potongan berdasarkan aturan yang benar
    $total_potongan = 0;
    $detail_potongan = [];

    // 1. Potongan BPJS Ketenagakerjaan: 2% dari gaji pokok
    $potongan_bpjs = $gaji_pokok * 0.02;
    if ($potongan_bpjs > 0) {
        $detail_potongan[] = [
            'nama' => 'Potongan BPJS Ketenagakerjaan (2% dari gaji pokok)', 
            'jumlah' => $potongan_bpjs
        ];
        $total_potongan += $potongan_bpjs;
    }

    // 2. Potongan Absensi: 3% dari gaji pokok per hari untuk Sakit, Izin, dan Alpha
    $stmt_absensi = $conn->prepare("SELECT Sakit, Izin, Alpha FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
    $stmt_absensi->bind_param("ssi", $id_karyawan, $bulan_nama, $tahun);
    $stmt_absensi->execute();
    $data_absensi = $stmt_absensi->get_result()->fetch_assoc();
    $stmt_absensi->close();

    if ($data_absensi) {
        $total_hari_tidak_hadir = ($data_absensi['Sakit'] ?? 0) + ($data_absensi['Izin'] ?? 0) + ($data_absensi['Alpha'] ?? 0);
        
        if ($total_hari_tidak_hadir > 0) {
            // Rumus: (3% x gaji pokok) x total absensi karyawan selama 1 bulan
            $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
            $detail_potongan[] = [
                'nama' => "Potongan Absensi ({$total_hari_tidak_hadir} hari x 3% gaji pokok)", 
                'jumlah' => $potongan_absensi
            ];
            $total_potongan += $potongan_absensi;
        }
    }
    
    // Gaji Bersih = Gaji Kotor - Potongan
    $gaji_bersih = $gaji_kotor - $total_potongan;
    
    // Gaji (untuk db_penggajian) = Gaji Pokok + Tunjangan + Lembur - Potongan
    // Ini sama dengan Gaji Bersih
    $gaji = $gaji_bersih;

    // --- TAMPILAN VIEW DETAIL YANG BARU ---
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
            <input type="hidden" name="Gaji_Pokok" value="<?= e($gaji_pokok) ?>">
            <input type="hidden" name="Total_Tunjangan" value="<?= e($total_tunjangan) ?>">
            <input type="hidden" name="Total_Lembur" value="<?= e($total_lembur) ?>">
            <input type="hidden" name="Total_Potongan" value="<?= e($total_potongan) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-sm font-medium text-gray-500">Nama Karyawan</span>
                    <span class="text-sm font-semibold text-gray-800"><?= e($karyawan['Nama_Karyawan']) ?></span>
                </div>
                 <div class="flex justify-between border-b pb-2">
                    <span class="text-sm font-medium text-gray-500">Jabatan</span>
                    <span class="text-sm font-semibold text-gray-800"><?= e($karyawan['Nama_Jabatan']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Tanggal Gaji</span>
                    <span class="text-sm font-semibold text-gray-800"><?= date('d F Y', strtotime($tgl_gaji)) ?></span>
                </div>
                 <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Masa Kerja</span>
                    <span class="text-sm font-semibold text-gray-800"><?= e($masa_kerja_text) ?></span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="font-bold text-lg text-green-700 mb-2">PENDAPATAN</h3>
                    <div class="border-t border-b border-gray-200 divide-y divide-gray-200">
                        <div class="flex justify-between py-2.5 px-3">
                            <span class="text-sm">Gaji Pokok</span>
                            <span class="text-sm font-semibold">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></span>
                        </div>
                        <?php foreach($all_tunjangan as $t): ?>
                        <div class="flex justify-between py-2.5 px-3">
                            <span class="text-sm">Tunjangan: <?= e($t['Nama_Tunjangan']) ?></span>
                            <span class="text-sm font-semibold">Rp <?= number_format($t['Jumlah_Tunjangan'], 0, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($jam_lembur > 0): ?>
                        <div class="flex justify-between py-2.5 px-3">
                            <span class="text-sm">Lembur (<?= $jam_lembur ?> jam × Rp <?= number_format($upah_per_jam_lembur, 0, ',', '.') ?>)</span>
                            <span class="text-sm font-semibold">Rp <?= number_format($total_lembur, 0, ',', '.') ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex justify-between py-2.5 px-3">
                            <span class="text-sm">Lembur</span>
                            <span class="text-sm font-semibold">Rp 0</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between py-2.5 px-3 bg-gray-50 rounded-b-md">
                        <span class="text-sm font-bold">Total Pendapatan (Gaji Kotor)</span>
                        <span class="text-sm font-bold">Rp <?= number_format($gaji_kotor, 0, ',', '.') ?></span>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-lg text-red-700 mb-2">POTONGAN</h3>
                    <div class="border-t border-b border-gray-200 divide-y divide-gray-200">
                        <?php if(!empty($detail_potongan)): ?>
                            <?php foreach($detail_potongan as $p): ?>
                                <div class="flex justify-between py-2.5 px-3">
                                    <span class="text-sm"><?= e($p['nama']) ?></span>
                                    <span class="text-sm font-semibold text-red-600">- Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="flex justify-between py-2.5 px-3">
                                <span class="text-sm text-gray-500">Tidak ada potongan</span>
                                <span class="text-sm font-semibold text-red-600">- Rp 0</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between py-2.5 px-3 bg-gray-50 rounded-b-md">
                        <span class="text-sm font-bold">Total Potongan</span>
                        <span class="text-sm font-bold text-red-600">- Rp <?= number_format($total_potongan, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- PERBAIKAN: Menampilkan rumus yang benar -->
            <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-lg">
                <h4 class="font-bold mb-2">RUMUS PERHITUNGAN:</h4>
                <div class="text-sm space-y-1">
                    <div>• Gaji Kotor = Gaji Pokok + Tunjangan + Lembur</div>
                    <div>• Gaji Bersih = Gaji Kotor - Potongan</div>
                    <div>• Gaji (Final) = Gaji Pokok + Tunjangan + Lembur - Potongan</div>
                    <div class="mt-2 pt-2 border-t border-blue-200">
                        <div><strong>Ketentuan Potongan:</strong></div>
                        <div>• BPJS: 2% dari gaji pokok</div>
                        <div>• Absensi: 3% dari gaji pokok per hari (Sakit + Izin + Alpha)</div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-blue-200">
                        <div><strong>Ketentuan Lembur:</strong></div>
                        <div>• Hanya untuk karyawan produksi</div>
                        <div>• Tarif: Rp 20.000 per jam</div>
                    </div>
                </div>
            </div>

            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-lg flex justify-between items-center">
                <span class="text-lg font-bold font-poppins">GAJI BERSIH (TAKE HOME PAY)</span>
                <span class="text-xl font-bold">Rp <?= number_format($gaji_bersih, 0, ',', '.') ?></span>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="salary.php?action=new_payroll" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali</a>
                <button type="submit" class="bg-green-600 text-white px-8 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    Ajukan Gaji
                </button>
            </div>
        </form>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
} 

// BAGIAN 3: JIKA HALAMAN DIAKSES LANGSUNG TANPA DATA POST

else {
    header('Location: salary.php?action=new_payroll');
    exit;
}
?>

<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

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

    $gaji_kotor = $_POST['Gaji_Kotor'] ?? 0;
    $total_tunjangan = $_POST['Total_Tunjangan'] ?? 0;
    $total_lembur = $_POST['Total_Lembur'] ?? 0;
    $total_potongan = $_POST['Total_Potongan'] ?? 0;
    $gaji_bersih = $_POST['Gaji_Bersih'] ?? 0;

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

    $stmt_insert = $conn->prepare(
        "INSERT INTO GAJI (Id_Gaji, Id_Karyawan, Tgl_Gaji, Total_Tunjangan, Total_Lembur, Total_Potongan, Gaji_Kotor, Gaji_Bersih, Status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan')"
    );
    $stmt_insert->bind_param("sssddddd", $id_gaji, $id_karyawan, $tgl_gaji, $total_tunjangan, $total_lembur, $total_potongan, $gaji_kotor, $gaji_bersih);
    
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

    $stmt_gapok = $conn->prepare("SELECT Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1");
    $stmt_gapok->bind_param("si", $karyawan['Id_Jabatan'], $masa_kerja_tahun);
    $stmt_gapok->execute();
    $gaji_pokok = $stmt_gapok->get_result()->fetch_assoc()['Nominal'] ?? 0;
    $stmt_gapok->close();

    // Mengambil semua jenis tunjangan dari tabel TUNJANGAN
    $all_tunjangan = $conn->query("SELECT Nama_Tunjangan, Jumlah_Tunjangan FROM TUNJANGAN")->fetch_all(MYSQLI_ASSOC);
    $total_tunjangan = array_sum(array_column($all_tunjangan, 'Jumlah_Tunjangan'));

    $jam_lembur = 5; // Contoh data lembur
    $upah_per_jam_lembur = $conn->query("SELECT Upah_Lembur FROM LEMBUR LIMIT 1")->fetch_assoc()['Upah_Lembur'] ?? 0;
    $total_lembur = $jam_lembur * $upah_per_jam_lembur;

    $gaji_kotor = $gaji_pokok + $total_tunjangan + $total_lembur;

    $total_potongan = 0;
    $potongan_list = $conn->query("SELECT Nama_Potongan, Tarif FROM POTONGAN");
    $detail_potongan = [];
    while ($p = $potongan_list->fetch_assoc()) {
        $jumlah_potongan_item = 0;
        $label_potongan = $p['Nama_Potongan'];

        if (stripos($p['Nama_Potongan'], 'bpjs') !== false) {
            $jumlah_potongan_item = $gaji_kotor * ($p['Tarif'] / 100);
            $label_potongan = "Potongan BPJS ({$p['Tarif']}%)";
        } else if (stripos($p['Nama_Potongan'], 'absensi') !== false) {
            $stmt_alpha = $conn->prepare("SELECT Alpha FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
            $stmt_alpha->bind_param("ssi", $id_karyawan, $bulan_nama, $tahun);
            $stmt_alpha->execute();
            $hari_alpha = $stmt_alpha->get_result()->fetch_assoc()['Alpha'] ?? 0;
            $stmt_alpha->close();
            $jumlah_potongan_item = $hari_alpha * $p['Tarif'];
            $label_potongan = "Potongan Absensi ({$hari_alpha} hari)";
        }

        if ($jumlah_potongan_item > 0) {
            $detail_potongan[] = ['nama' => $label_potongan, 'jumlah' => $jumlah_potongan_item];
            $total_potongan += $jumlah_potongan_item;
        }
    }
    
    $gaji_bersih = $gaji_kotor - $total_potongan;

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
            <input type="hidden" name="Gaji_Kotor" value="<?= e($gaji_kotor) ?>">
            <input type="hidden" name="Total_Tunjangan" value="<?= e($total_tunjangan) ?>">
            <input type="hidden" name="Total_Lembur" value="<?= e($total_lembur) ?>">
            <input type="hidden" name="Total_Potongan" value="<?= e($total_potongan) ?>">
            <input type="hidden" name="Gaji_Bersih" value="<?= e($gaji_bersih) ?>">

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
                        <div class="flex justify-between py-2.5 px-3">
                            <span class="text-sm">Lembur (<?= $jam_lembur ?> jam)</span>
                            <span class="text-sm font-semibold">Rp <?= number_format($total_lembur, 0, ',', '.') ?></span>
                        </div>
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
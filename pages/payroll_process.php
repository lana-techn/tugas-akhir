<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();

// =================================================================
// BAGIAN 1: PROSES PENGAJUAN FINAL (SAAT TOMBOL 'AJUKAN' DITEKAN)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_gaji'])) {

    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'] ?? null;
    $periode = $_POST['periode'] ?? null;

    // Validasi kritis untuk memastikan periode ada dan formatnya benar
    if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        set_flash_message('error', 'Periode gaji tidak valid saat pengajuan. Proses dibatalkan.');
        header('Location: salary.php?action=new_payroll');
        exit;
    }
    
    // Konversi periode YYYY-MM menjadi tanggal YYYY-MM-DD (hari terakhir bulan itu)
    $periode_parts = explode('-', $periode);
    if (count($periode_parts) !== 2 || !checkdate($periode_parts[1], 1, $periode_parts[0])) {
        set_flash_message('error', 'Format periode tidak valid.');
        header('Location: salary.php?action=new_payroll');
        exit;
    }
    
    // Create a DateTime object for the first day of the month
    $date = new DateTime($periode . '-01');
    // Move to the last day of the month
    $date->modify('last day of this month');
    // Format the date for MySQL
    $tgl_gaji = $date->format('Y-m-d');

    // Ambil semua data yang sudah dihitung dari hidden input
    $gaji_kotor = $_POST['Gaji_Kotor'] ?? 0;
    $total_tunjangan = $_POST['Total_Tunjangan'] ?? 0;
    $total_lembur = $_POST['Total_Lembur'] ?? 0;
    $total_potongan = $_POST['Total_Potongan'] ?? 0;
    $gaji_bersih = $_POST['Gaji_Bersih'] ?? 0;

    // Buat ID Gaji yang unik
    $periode_id = date('Ym', strtotime($tgl_gaji));
    $id_gaji = "G-" . $periode_id . "-" . $id_karyawan;

    // Cek duplikasi sebelum insert
    $stmt_cek = $conn->prepare("SELECT Id_Gaji FROM GAJI WHERE Id_Gaji = ?");
    $stmt_cek->bind_param("s", $id_gaji);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows > 0) {
        set_flash_message('error', "Gaji untuk karyawan ini pada periode " . date('F Y', strtotime($tgl_gaji)) . " sudah pernah diajukan.");
        header('Location: salary.php?action=new_payroll');
        exit;
    }
    $stmt_cek->close();

    // Simpan data final ke tabel GAJI
    $stmt_insert = $conn->prepare(
        "INSERT INTO GAJI (Id_Gaji, Id_Karyawan, Tgl_Gaji, Total_Tunjangan, Total_Lembur, Total_Potongan, Gaji_Kotor, Gaji_Bersih, Status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan')"
    );
    echo "Preparing to insert with values:\n";
    echo "- Id_Gaji: $id_gaji\n";
    echo "- Id_Karyawan: $id_karyawan\n";
    echo "- Tgl_Gaji: $tgl_gaji\n";
    echo "- Total_Tunjangan: $total_tunjangan\n";
    echo "- Total_Lembur: $total_lembur\n";
    echo "- Total_Potongan: $total_potongan\n";
    echo "- Gaji_Kotor: $gaji_kotor\n";
    echo "- Gaji_Bersih: $gaji_bersih\n";
    // Format the date properly for MySQL
    $formatted_date = date('Y-m-d', strtotime($tgl_gaji));
    
    $stmt_insert->bind_param(
        "sssddddd",
        $id_gaji, $id_karyawan, $formatted_date, $total_tunjangan, $total_lembur, $total_potongan, $gaji_kotor, $gaji_bersih
    );
    
    echo "Debug - Formatted date: $formatted_date\n";

    echo "Debug - Query parameters:\n";
    echo "SQL Query: INSERT INTO GAJI (Id_Gaji, Id_Karyawan, Tgl_Gaji, Total_Tunjangan, Total_Lembur, Total_Potongan, Gaji_Kotor, Gaji_Bersih, Status) VALUES ('$id_gaji', '$id_karyawan', '$tgl_gaji', $total_tunjangan, $total_lembur, $total_potongan, $gaji_kotor, $gaji_bersih, 'Diajukan')\n";
    echo "Id_Gaji: $id_gaji\n";
    echo "Id_Karyawan: $id_karyawan\n";
    echo "Tgl_Gaji: $tgl_gaji\n";
    
    if ($stmt_insert->execute()) {
        set_flash_message('success', 'Gaji berhasil diajukan dan menunggu persetujuan pemilik.');
    } else {
        echo "MySQL Error: " . $stmt_insert->error . "\n";
        set_flash_message('error', 'Gagal mengajukan gaji: ' . $stmt_insert->error);
    }
    $stmt_insert->close();
    if (!defined('TEST_MODE')) {
        header('Location: salary.php?action=list_gapok'); // Arahkan kembali ke daftar utama
    }
    exit;

} 
// =================================================================
// BAGIAN 2: TAMPILAN DETAIL GAJI (SAAT DATA DITERIMA DARI FORM AWAL)
// =================================================================
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id_Karyawan']) && isset($_POST['periode'])) {
    
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'];
    $periode = $_POST['periode'];
    
    // Validasi input awal
    if (empty($id_karyawan) || empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        set_flash_message('error', 'Karyawan atau Periode tidak valid. Silakan pilih kembali.');
        header('Location: salary.php?action=new_payroll');
        exit;
    }

    // --- MULAI KALKULASI OTOMATIS ---
    $tgl_gaji = date('Y-m-t', strtotime($periode)); 
    $bulan_nama = date('F', strtotime($tgl_gaji));
    $tahun = date('Y', strtotime($tgl_gaji));

    // Ambil data karyawan dan jabatan
    $stmt_karyawan = $conn->prepare("SELECT k.*, j.Nama_Jabatan FROM KARYAWAN k JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan WHERE k.Id_Karyawan = ?");
    $stmt_karyawan->bind_param("s", $id_karyawan);
    $stmt_karyawan->execute();
    $karyawan = $stmt_karyawan->get_result()->fetch_assoc();
    if (!$karyawan) die('Karyawan tidak ditemukan.');
    $stmt_karyawan->close();
    
    // Hitung masa kerja
    $tgl_awal_kerja = new DateTime($karyawan['Tgl_Awal_Kerja']);
    $masa_kerja_tahun = (new DateTime($tgl_gaji))->diff($tgl_awal_kerja)->y;
    $masa_kerja_text = (new DateTime($tgl_gaji))->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');

    // Ambil Gaji Pokok berdasarkan jabatan dan masa kerja
    $stmt_gapok = $conn->prepare("SELECT Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1");
    $stmt_gapok->bind_param("si", $karyawan['Id_Jabatan'], $masa_kerja_tahun);
    $stmt_gapok->execute();
    $gaji_pokok = $stmt_gapok->get_result()->fetch_assoc()['Nominal'] ?? 0;
    $stmt_gapok->close();

    // Ambil Total Tunjangan (asumsi semua tunjangan diberikan)
    $result_tunjangan = $conn->query("SELECT SUM(Jumlah_Tunjangan) as total FROM TUNJANGAN");
    $total_tunjangan = $result_tunjangan->fetch_assoc()['total'] ?? 0;

    // Hitung Lembur (contoh: 5 jam lembur)
    $jam_lembur = 5; 
    $upah_per_jam_lembur = $conn->query("SELECT Upah_Lembur FROM LEMBUR LIMIT 1")->fetch_assoc()['Upah_Lembur'] ?? 0;
    $total_lembur = $jam_lembur * $upah_per_jam_lembur;

    // Hitung Gaji Kotor
    $gaji_kotor = $gaji_pokok + $total_tunjangan + $total_lembur;

    // Hitung Potongan
    $total_potongan = 0;
    $potongan_list = $conn->query("SELECT Nama_Potongan, Tarif FROM POTONGAN");
    $detail_potongan = [];
    while ($p = $potongan_list->fetch_assoc()) {
        $jumlah_potongan_item = 0;
        if (stripos($p['Nama_Potongan'], 'bpjs') !== false) {
            $jumlah_potongan_item = $gaji_kotor * ($p['Tarif'] / 100);
        } else if (stripos($p['Nama_Potongan'], 'absensi') !== false) {
            $stmt_alpha = $conn->prepare("SELECT Alpha FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
            $stmt_alpha->bind_param("ssi", $id_karyawan, $bulan_nama, $tahun);
            $stmt_alpha->execute();
            $hari_alpha = $stmt_alpha->get_result()->fetch_assoc()['Alpha'] ?? 0;
            $stmt_alpha->close();
            $jumlah_potongan_item = $hari_alpha * $p['Tarif'];
        }
        if ($jumlah_potongan_item > 0) {
            $detail_potongan[] = ['nama' => $p['Nama_Potongan'], 'jumlah' => $jumlah_potongan_item];
            $total_potongan += $jumlah_potongan_item;
        }
    }
    
    // Hitung Gaji Bersih
    $gaji_bersih = $gaji_kotor - $total_potongan;

    // --- TAMPILKAN VIEW DETAIL ---
    generate_csrf_token();
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Detail Pengajuan Gaji</h2>

        <!-- Informasi Karyawan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 mb-6 border-b pb-6">
            <div><p class="text-sm text-gray-500">Tanggal Gaji</p><p class="font-semibold"><?= e(date('d F Y', strtotime($tgl_gaji))) ?></p></div>
            <div><p class="text-sm text-gray-500">Masa Kerja</p><p class="font-semibold"><?= e($masa_kerja_text) ?></p></div>
            <div><p class="text-sm text-gray-500">Nama Karyawan</p><p class="font-semibold"><?= e($karyawan['Nama_Karyawan']) ?></p></div>
            <div><p class="text-sm text-gray-500">Jabatan</p><p class="font-semibold"><?= e($karyawan['Nama_Jabatan']) ?></p></div>
        </div>

        <!-- Form untuk mengirim data final -->
        <form method="POST" action="payroll_process.php">
            <?php csrf_input(); ?>
            <!-- Hidden inputs untuk membawa semua data yang sudah dihitung ke proses final -->
            <input type="hidden" name="ajukan_gaji" value="1">
            <input type="hidden" name="Id_Karyawan" value="<?= e($id_karyawan) ?>">
            <input type="hidden" name="periode" value="<?= e($periode) ?>"> 
            <input type="hidden" name="Gaji_Kotor" value="<?= e($gaji_kotor) ?>">
            <input type="hidden" name="Total_Tunjangan" value="<?= e($total_tunjangan) ?>">
            <input type="hidden" name="Total_Lembur" value="<?= e($total_lembur) ?>">
            <input type="hidden" name="Total_Potongan" value="<?= e($total_potongan) ?>">
            <input type="hidden" name="Gaji_Bersih" value="<?= e($gaji_bersih) ?>">

            <!-- Rincian Gaji -->
            <div class="space-y-4">
                <div class="flex justify-between items-center"><span class="font-semibold">Gaji Pokok</span><span>Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></span></div>
                <div class="flex justify-between items-center"><span class="font-semibold">Tunjangan</span><span>Rp <?= number_format($total_tunjangan, 0, ',', '.') ?></span></div>
                <div class="flex justify-between items-center"><span class="font-semibold">Lembur</span><span>Rp <?= number_format($total_lembur, 0, ',', '.') ?></span></div>
                <hr>
                <div class="flex justify-between items-center font-bold text-lg"><span>Gaji Kotor</span><span>Rp <?= number_format($gaji_kotor, 0, ',', '.') ?></span></div>
                <hr>
                
                <?php if (!empty($detail_potongan)): ?>
                    <?php foreach($detail_potongan as $p): ?>
                    <div class="flex justify-between items-center text-red-600"><span>Potongan: <?= e($p['nama']) ?></span><span>- Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></span></div>
                    <?php endforeach; ?>
                    <hr>
                <?php endif; ?>
                <div class="flex justify-between items-center font-semibold text-red-700"><span>Total Potongan</span><span>- Rp <?= number_format($total_potongan, 0, ',', '.') ?></span></div>
                <hr>

                <div class="flex justify-between items-center font-bold text-xl text-green-700 bg-green-50 p-4 rounded-lg"><span>Gaji Bersih (Take Home Pay)</span><span>Rp <?= number_format($gaji_bersih, 0, ',', '.') ?></span></div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="salary.php?action=new_payroll" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 font-semibold text-sm">Kembali</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold text-sm">Ajukan</button>
            </div>
        </form>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
} 
// =================================================================
// BAGIAN 3: JIKA HALAMAN DIAKSES LANGSUNG TANPA DATA POST
// =================================================================
else {
    // Redirect ke halaman awal jika diakses secara tidak benar
    header('Location: salary.php?action=new_payroll');
    exit;
}
?>

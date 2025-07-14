<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'view';
$page_title = 'Manajemen Lembur';

// Pastikan hanya ada satu data lembur dengan tarif Rp 20.000
$stmt_check = $conn->query("SELECT COUNT(*) as count FROM LEMBUR");
$count = $stmt_check->fetch_assoc()['count'];

if ($count == 0) {
    // Insert data lembur default jika belum ada
    $stmt_insert = $conn->prepare("INSERT INTO LEMBUR (Nama_Lembur, Upah_Lembur, Keterangan) VALUES (?, ?, ?)");
    $nama_lembur = "Lembur Produksi";
    $upah_lembur = 20000;
    $keterangan = "Lembur hanya dilakukan oleh karyawan bagian produksi dan tidak dilakukan setiap hari, namun hanya ketika terjadi lonjakan pesanan barang dengan besarnya upah lembur per jam Rp. 20.000.";
    $stmt_insert->bind_param("sis", $nama_lembur, $upah_lembur, $keterangan);
    $stmt_insert->execute();
    $stmt_insert->close();
}

// --- PROSES EDIT TARIF LEMBUR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lembur'])) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $upah_lembur = filter_input(INPUT_POST, 'Upah_Lembur', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['Keterangan']);

    if ($upah_lembur === false || $upah_lembur <= 0) {
        set_flash_message('error', 'Upah lembur harus berupa angka positif.');
    } else {
        // Update semua data lembur dengan tarif yang sama
        $stmt = $conn->prepare("UPDATE LEMBUR SET Upah_Lembur = ?, Keterangan = ?");
        $stmt->bind_param("is", $upah_lembur, $keterangan);
        
        if ($stmt->execute()) {
            set_flash_message('success', "Tarif lembur berhasil diperbarui menjadi Rp " . number_format($upah_lembur, 0, ',', '.') . " per jam.");
        } else {
            set_flash_message('error', "Gagal memperbarui tarif lembur: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: lembur.php?action=view');
    exit;
}

// Ambil data lembur saat ini
$stmt_lembur = $conn->query("SELECT * FROM LEMBUR LIMIT 1");
$lembur_data = $stmt_lembur->fetch_assoc();

generate_csrf_token();

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . '/../includes/header.php';
?>

<?php display_flash_message(); ?>

<div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl mx-auto">
    <div class="text-center mb-8">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
            <i class="fa-solid fa-clock text-2xl text-blue-600"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-800 font-poppins">Pengaturan Lembur</h2>
        <p class="text-gray-500 mt-2">Kelola tarif upah lembur untuk karyawan produksi</p>
    </div>

    <!-- Informasi Kebijakan Lembur -->
    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-lg mb-6">
        <h4 class="font-bold mb-2">KEBIJAKAN LEMBUR:</h4>
        <div class="text-sm space-y-1">
            <div>• Lembur hanya dilakukan oleh karyawan bagian produksi</div>
            <div>• Lembur tidak dilakukan setiap hari</div>
            <div>• Lembur hanya ketika terjadi lonjakan pesanan barang</div>
            <div>• Tarif lembur berlaku untuk semua karyawan produksi</div>
        </div>
    </div>

    <!-- Form Edit Tarif Lembur -->
    <form method="POST" action="lembur.php" class="space-y-6">
        <?php csrf_input(); ?>
        <input type="hidden" name="update_lembur" value="1">
        
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label for="Nama_Lembur" class="block mb-2 text-sm font-medium text-gray-700">Jenis Lembur</label>
                <input type="text" id="Nama_Lembur" value="<?= e($lembur_data['Nama_Lembur'] ?? 'Lembur Produksi') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                <p class="text-xs text-gray-500 mt-1">Jenis lembur tidak dapat diubah</p>
            </div>
            
            <div>
                <label for="Upah_Lembur" class="block mb-2 text-sm font-medium text-gray-700">Upah Lembur per Jam (Rp)</label>
                <input type="number" id="Upah_Lembur" name="Upah_Lembur" value="<?= e($lembur_data['Upah_Lembur'] ?? 20000) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required min="1000" step="1000">
                <p class="text-xs text-gray-500 mt-1">Minimal Rp 1.000, kelipatan Rp 1.000</p>
            </div>
        </div>

        <div>
            <label for="Keterangan" class="block mb-2 text-sm font-medium text-gray-700">Keterangan</label>
            <textarea id="Keterangan" name="Keterangan" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($lembur_data['Keterangan'] ?? '') ?></textarea>
        </div>

        <!-- Tampilan Tarif Saat Ini -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-green-800">Tarif Lembur Saat Ini</h4>
                    <p class="text-sm text-green-600">Berlaku untuk semua karyawan produksi</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-green-800">Rp <?= number_format($lembur_data['Upah_Lembur'] ?? 20000, 0, ',', '.') ?></div>
                    <div class="text-sm text-green-600">per jam</div>
                </div>
            </div>
        </div>

        <!-- Contoh Perhitungan -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="font-bold text-gray-800 mb-3">Contoh Perhitungan:</h4>
            <div class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between">
                    <span>Karyawan lembur 10 jam dalam sebulan:</span>
                    <span class="font-semibold">10 jam × Rp <?= number_format($lembur_data['Upah_Lembur'] ?? 20000, 0, ',', '.') ?> = Rp <?= number_format(10 * ($lembur_data['Upah_Lembur'] ?? 20000), 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Karyawan lembur 15 jam dalam sebulan:</span>
                    <span class="font-semibold">15 jam × Rp <?= number_format($lembur_data['Upah_Lembur'] ?? 20000, 0, ',', '.') ?> = Rp <?= number_format(15 * ($lembur_data['Upah_Lembur'] ?? 20000), 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-end space-x-4 pt-4">
            <a href="salary.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali ke Gaji</a>
            <button type="submit" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-save"></i>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>


<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list_gapok';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Gaji';

// Ambil data untuk dropdown forms
$jabatan_list = $conn->query("SELECT Id_Jabatan, Nama_Jabatan FROM JABATAN ORDER BY Nama_Jabatan")->fetch_all(MYSQLI_ASSOC);
$karyawan_list = $conn->query("SELECT Id_Karyawan, Nama_Karyawan FROM KARYAWAN WHERE Status = 'Aktif' ORDER BY Nama_Karyawan")->fetch_all(MYSQLI_ASSOC);

// --- PROSES HAPUS GAJI POKOK ---
if ($action === 'delete_gapok' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM GAJI_POKOK WHERE Id_Gapok = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji pokok berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data gaji pokok.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: salary.php?action=list_gapok');
    exit;
}

// --- PROSES TAMBAH & EDIT GAJI POKOK---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'gapok') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_gapok = $_POST['Id_Gapok'] ?? null;
    $id_jabatan = $_POST['Id_Jabatan'];
    $masa_kerja = filter_input(INPUT_POST, 'Masa_Kerja', FILTER_VALIDATE_INT);
    $nominal = filter_input(INPUT_POST, 'Nominal', FILTER_VALIDATE_INT);

    if (empty($id_jabatan) || $masa_kerja === false || $nominal === false) {
        set_flash_message('error', 'Semua kolom wajib diisi dengan format yang benar.');
    } else {
        if ($id_gapok) { // Edit
            $stmt = $conn->prepare("UPDATE GAJI_POKOK SET Id_Jabatan=?, Masa_Kerja=?, Nominal=? WHERE Id_Gapok=?");
            $stmt->bind_param("siii", $id_jabatan, $masa_kerja, $nominal, $id_gapok);
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt = $conn->prepare("INSERT INTO GAJI_POKOK (Id_Jabatan, Masa_Kerja, Nominal) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $id_jabatan, $masa_kerja, $nominal);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Gaji pokok berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses gaji pokok: " . $stmt->error);
        
        $stmt->close();
        header('Location: salary.php?action=list_gapok');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT GAJI POKOK ---
$gapok_data = null;
if ($action === 'edit_gapok' && $id) {
    $page_title = 'Edit Gaji Pokok';
    $stmt = $conn->prepare("SELECT * FROM GAJI_POKOK WHERE Id_Gapok = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $gapok_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$gapok_data) {
        set_flash_message('error', 'Data Gaji Pokok tidak ditemukan.');
        header('Location: salary.php?action=list_gapok');
        exit;
    }
} elseif ($action === 'add_gapok') {
    $page_title = 'Tambah Gaji Pokok';
} elseif ($action === 'new_payroll') {
    $page_title = 'Pengajuan Gaji Baru';
}

generate_csrf_token();
$conn->close();

// 2. MEMANGGIL TAMPILAN (VIEW)
// =======================================
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<?php display_flash_message(); ?>

<?php if ($action === 'list_gapok'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Gaji Pokok</h2>
            <a href="salary.php?action=add_gapok" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Gaji Pokok
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Jabatan</th>
                        <th class="px-4 py-3 text-center">Masa Kerja (Thn)</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $conn = db_connect();
                    $result = $conn->query("SELECT gp.*, j.Nama_Jabatan FROM GAJI_POKOK gp JOIN JABATAN j ON gp.Id_Jabatan = j.Id_Jabatan ORDER BY j.Nama_Jabatan, gp.Masa_Kerja");
                    $no = 1;
                    while ($row = $result->fetch_assoc()): 
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Nama_Jabatan']) ?></td>
                        <td class="px-4 py-3 text-center"><?= e($row['Masa_Kerja']) ?></td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($row['Nominal'] ?? 0, 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="salary.php?action=edit_gapok&id=<?= e($row['Id_Gapok']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="salary.php?action=delete_gapok&id=<?= e($row['Id_Gapok']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; $conn->close(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add_gapok' || $action === 'edit_gapok'): ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= $action === 'add_gapok' ? 'Tambah' : 'Edit' ?> Gaji Pokok</h2>
        <form method="POST" action="salary.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="form_type" value="gapok">
            <input type="hidden" name="Id_Gapok" value="<?= e($gapok_data['Id_Gapok'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Id_Jabatan" class="block mb-2 text-sm font-bold text-gray-700">Jabatan</label>
                <select id="Id_Jabatan" name="Id_Jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="">- Pilih Jabatan -</option>
                    <?php foreach($jabatan_list as $jabatan): ?>
                    <option value="<?= e($jabatan['Id_Jabatan']) ?>" <?= (isset($gapok_data) && $gapok_data['Id_Jabatan'] == $jabatan['Id_Jabatan']) ? 'selected' : '' ?>>
                        <?= e($jabatan['Nama_Jabatan']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-5">
                <label for="Masa_Kerja" class="block mb-2 text-sm font-bold text-gray-700">Masa Kerja (Tahun)</label>
                <input type="number" id="Masa_Kerja" name="Masa_Kerja" value="<?= e($gapok_data['Masa_Kerja'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="mb-8">
                <label for="Nominal" class="block mb-2 text-sm font-bold text-gray-700">Nominal Gaji Pokok (Rp)</label>
                <input type="number" id="Nominal" name="Nominal" value="<?= e($gapok_data['Nominal'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="flex items-center justify-end space-x-4">
                <a href="salary.php?action=list_gapok" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($action === 'new_payroll'): ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8">Pengajuan Gaji Baru</h2>
        <form method="POST" action="payroll_process.php"> <!-- Arahkan ke file proses terpisah -->
            <?php csrf_input(); ?>
            <div class="mb-5">
                <label for="Id_Karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                <select name="Id_Karyawan" id="Id_Karyawan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="">- Pilih Karyawan -</option>
                    <?php foreach($karyawan_list as $karyawan): ?>
                        <option value="<?= e($karyawan['Id_Karyawan']) ?>"><?= e($karyawan['Nama_Karyawan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-8">
                <label for="periode" class="block mb-2 text-sm font-bold text-gray-700">Periode Gaji</label>
                <input type="month" name="periode" id="periode" value="<?= date('Y-m') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="flex items-center justify-end space-x-4">
                <a href="salary.php?action=list_gapok" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Proses Gaji</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
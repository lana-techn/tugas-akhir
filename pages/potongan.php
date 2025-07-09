<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Potongan';

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM POTONGAN WHERE Id_Potongan = ?");
        $stmt->bind_param("i", $id); // Id_Potongan adalah INT
        if ($stmt->execute()) {
            set_flash_message('success', 'Data potongan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data potongan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: potongan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_potongan = $_POST['Id_Potongan'] ?? null;
    $nama_potongan = trim($_POST['Nama_Potongan']);
    $tarif = filter_input(INPUT_POST, 'Tarif', FILTER_VALIDATE_FLOAT);
    $keterangan = trim($_POST['Keterangan']);

    if (empty($nama_potongan) || $tarif === false) {
        set_flash_message('error', 'Nama potongan dan tarif wajib diisi dengan format yang benar.');
    } else {
        if ($id_potongan) { // Edit
            $stmt = $conn->prepare("UPDATE POTONGAN SET Nama_Potongan = ?, Tarif = ?, Keterangan = ? WHERE Id_Potongan = ?");
            $stmt->bind_param("sdsi", $nama_potongan, $tarif, $keterangan, $id_potongan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt = $conn->prepare("INSERT INTO POTONGAN (Nama_Potongan, Tarif, Keterangan) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $nama_potongan, $tarif, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data potongan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data potongan: " . $stmt->error);
        
        $stmt->close();
        header('Location: potongan.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$potongan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Potongan';
    $stmt = $conn->prepare("SELECT * FROM POTONGAN WHERE Id_Potongan = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $potongan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$potongan_data) {
        set_flash_message('error', 'Data potongan tidak ditemukan.');
        header('Location: potongan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Data Potongan';
}

generate_csrf_token();
$conn->close();

// Memanggil header.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Potongan</h2>
            <a href="potongan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Potongan
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Potongan</th>
                        <th class="px-4 py-3 text-right">Tarif</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM POTONGAN ORDER BY Nama_Potongan ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Nama_Potongan']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php
                                // Cek jika potongan berbasis persentase
                                if (strpos(strtolower($row['Nama_Potongan']), 'bpjs') !== false || strpos(strtolower($row['Nama_Potongan']), '%') !== false) {
                                    echo e($row['Tarif']) . '%';
                                } else {
                                    echo 'Rp ' . number_format($row['Tarif'] ?? 0, 0, ',', '.');
                                }
                            ?>
                        </td>
                        <td class="px-4 py-3"><?= e($row['Keterangan']) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="potongan.php?action=edit&id=<?= e($row['Id_Potongan']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="potongan.php?action=delete&id=<?= e($row['Id_Potongan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; $conn->close(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Data Potongan</h2>
        <form method="POST" action="potongan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Potongan" value="<?= e($potongan_data['Id_Potongan'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Nama_Potongan" class="block mb-2 text-sm font-bold text-gray-700">Nama Potongan</label>
                <input type="text" id="Nama_Potongan" name="Nama_Potongan" value="<?= e($potongan_data['Nama_Potongan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="Tarif" class="block mb-2 text-sm font-bold text-gray-700">Tarif</label>
                <input type="number" step="0.01" id="Tarif" name="Tarif" value="<?= e($potongan_data['Tarif'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <p class="text-xs text-gray-500 mt-1">Isi angka saja. Jika berbasis persentase (misal: 2.5% untuk BPJS), cukup isi `2.5`.</p>
            </div>

            <div class="mb-8">
                <label for="Keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                <textarea id="Keterangan" name="Keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?= e($potongan_data['Keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="potongan.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
// Memanggil footer.php
require_once __DIR__ . '/../includes/footer.php';
?>
<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Lembur';

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM lembur WHERE id_lembur = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data lembur berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data lembur.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: lembur.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_lembur = $_POST['id_lembur'] ?? null;
    $nama_lembur = trim($_POST['nama_lembur']);
    $upah_lembur = filter_input(INPUT_POST, 'upah_lembur', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama_lembur) || $upah_lembur === false) {
        set_flash_message('error', 'Nama lembur dan upah lembur wajib diisi dengan format yang benar.');
    } else {
        if ($id_lembur) { // Edit
            $stmt = $conn->prepare("UPDATE lembur SET nama_lembur = ?, upah_lembur = ?, keterangan = ? WHERE id_lembur = ?");
            $stmt->bind_param("siss", $nama_lembur, $upah_lembur, $keterangan, $id_lembur);
            $action_text = 'diperbarui';
        } else { // Tambah
            $result = $conn->query("SELECT id_lembur FROM lembur ORDER BY id_lembur DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_lembur'], 1)) : 0;
            $id_lembur_new = 'L' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO lembur (id_lembur, nama_lembur, upah_lembur, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $id_lembur_new, $nama_lembur, $upah_lembur, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data lembur berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data lembur.");
        }
        $stmt->close();
        header('Location: lembur.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$lembur_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Lembur';
    $stmt = $conn->prepare("SELECT * FROM lembur WHERE id_lembur = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $lembur_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lembur_data) {
        set_flash_message('error', 'Data lembur tidak ditemukan.');
        header('Location: lembur.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Data Lembur';
}

generate_csrf_token();
$conn->close();

// 2. MEMANGGIL TAMPILAN (VIEW)
// =======================================
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Upah Lembur</h2>
            <a href="lembur.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Data
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama/Jenis Lembur</th>
                        <th class="px-4 py-3">Upah per Jam</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM lembur ORDER BY nama_lembur ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['nama_lembur']) ?></td>
                        <td class="px-4 py-3">Rp <?= number_format($row['upah_lembur'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3"><?= e($row['keterangan']) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="lembur.php?action=edit&id=<?= e($row['id_lembur']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="lembur.php?action=delete&id=<?= e($row['id_lembur']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Data Lembur</h2>
        <form method="POST" action="lembur.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_lembur" value="<?= e($lembur_data['id_lembur'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="nama_lembur" class="block mb-2 text-sm font-bold text-gray-700">Nama/Jenis Lembur</label>
                <input type="text" id="nama_lembur" name="nama_lembur" value="<?= e($lembur_data['nama_lembur'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="upah_lembur" class="block mb-2 text-sm font-bold text-gray-700">Upah Lembur per Jam (Rp)</label>
                <input type="number" id="upah_lembur" name="upah_lembur" value="<?= e($lembur_data['upah_lembur'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>

            <div class="mb-8">
                <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?= e($lembur_data['keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="lembur.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Tunjangan';

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM tunjangan WHERE id_tunjangan = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data tunjangan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data tunjangan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: tunjangan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_tunjangan = $_POST['id_tunjangan'] ?? null;
    $nama_tunjangan = trim($_POST['nama_tunjangan']);
    $jumlah = filter_input(INPUT_POST, 'jumlah_tunjangan', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama_tunjangan) || $jumlah === false) {
        set_flash_message('error', 'Nama dan jumlah tunjangan wajib diisi dengan format yang benar.');
    } else {
        if ($id_tunjangan) {
            $stmt = $conn->prepare("UPDATE tunjangan SET nama_tunjangan = ?, jumlah_tunjangan = ?, keterangan = ? WHERE id_tunjangan = ?");
            $stmt->bind_param("siss", $nama_tunjangan, $jumlah, $keterangan, $id_tunjangan);
            $action_text = 'diperbarui';
        } else {
            $result = $conn->query("SELECT id_tunjangan FROM tunjangan ORDER BY id_tunjangan DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_tunjangan'], 2)) : 0;
            $id_tunjangan_new = 'TJ' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO tunjangan (id_tunjangan, nama_tunjangan, jumlah_tunjangan, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $id_tunjangan_new, $nama_tunjangan, $jumlah, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data tunjangan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data tunjangan.");
        
        $stmt->close();
        header('Location: tunjangan.php?action=list');
        exit;
    }
}

$tunjangan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Tunjangan';
    $stmt = $conn->prepare("SELECT * FROM tunjangan WHERE id_tunjangan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $tunjangan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tunjangan_data) {
        set_flash_message('error', 'Data tunjangan tidak ditemukan.');
        header('Location: tunjangan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Data Tunjangan';
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
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Tunjangan</h2>
            <a href="tunjangan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold">Tambah Tunjangan</a>
        </div>
        <div class="overflow-x-auto mt-6">
            <table class="w-full text-sm text-center text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 border">Nama Tunjangan</th>
                        <th class="px-4 py-3 border">Jumlah</th>
                        <th class="px-4 py-3 border">Keterangan</th>
                        <th class="px-4 py-3 border">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM tunjangan ORDER BY nama_tunjangan ASC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 border text-left"><?= e($row['nama_tunjangan']) ?></td>
                        <td class="px-4 py-3 border">Rp <?= number_format($row['jumlah_tunjangan'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 border text-left"><?= e($row['keterangan']) ?></td>
                        <td class="px-4 py-3 border space-x-2">
                            <a href="tunjangan.php?action=edit&id=<?= e($row['id_tunjangan']) ?>" class="bg-blue-500 text-white text-xs px-3 py-1 rounded hover:bg-blue-600">Edit</a>
                            <a href="tunjangan.php?action=delete&id=<?= e($row['id_tunjangan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs px-3 py-1 rounded hover:bg-red-600" onclick="return confirm('Yakin?')">Hapus</a>
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
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6"><?= e(ucfirst($action)) ?> Data Tunjangan</h2>
        <form method="POST" action="tunjangan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_tunjangan" value="<?= e($tunjangan_data['id_tunjangan'] ?? '') ?>">
            
            <div class="mb-4">
                <label for="nama_tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Nama Tunjangan</label>
                <input type="text" id="nama_tunjangan" name="nama_tunjangan" value="<?= e($tunjangan_data['nama_tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500" required>
            </div>
            
            <div class="mb-4">
                <label for="jumlah_tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Jumlah Tunjangan (Rp)</label>
                <input type="number" id="jumlah_tunjangan" name="jumlah_tunjangan" value="<?= e($tunjangan_data['jumlah_tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500" required>
            </div>

            <div class="mb-6">
                <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500"><?= e($tunjangan_data['keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="tunjangan.php?action=list" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
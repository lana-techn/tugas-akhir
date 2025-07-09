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
        $stmt = $conn->prepare("DELETE FROM TUNJANGAN WHERE Id_Tunjangan = ?");
        $stmt->bind_param("i", $id); // ID adalah INT AUTO_INCREMENT
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

    $id_tunjangan = $_POST['Id_Tunjangan'] ?? null;
    $nama_tunjangan = trim($_POST['Nama_Tunjangan']);
    $jumlah = filter_input(INPUT_POST, 'Jumlah_Tunjangan', FILTER_VALIDATE_FLOAT);
    $keterangan = trim($_POST['Keterangan']);

    if (empty($nama_tunjangan) || $jumlah === false) {
        set_flash_message('error', 'Nama dan jumlah tunjangan wajib diisi dengan format yang benar.');
    } else {
        if ($id_tunjangan) { // Edit
            $stmt = $conn->prepare("UPDATE TUNJANGAN SET Nama_Tunjangan = ?, Jumlah_Tunjangan = ?, Keterangan = ? WHERE Id_Tunjangan = ?");
            $stmt->bind_param("sdsi", $nama_tunjangan, $jumlah, $keterangan, $id_tunjangan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt = $conn->prepare("INSERT INTO TUNJANGAN (Nama_Tunjangan, Jumlah_Tunjangan, Keterangan) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $nama_tunjangan, $jumlah, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data tunjangan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data tunjangan: " . $stmt->error);
        
        $stmt->close();
        header('Location: tunjangan.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$tunjangan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Tunjangan';
    $stmt = $conn->prepare("SELECT * FROM TUNJANGAN WHERE Id_Tunjangan = ?");
    $stmt->bind_param("i", $id);
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
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Tunjangan</h2>
            <a href="tunjangan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Tunjangan
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Tunjangan</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM TUNJANGAN ORDER BY Nama_Tunjangan ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Nama_Tunjangan']) ?></td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($row['Jumlah_Tunjangan'] ?? 0, 0, ',', '.') ?></td>
                        <td class="px-4 py-3"><?= e($row['Keterangan']) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="tunjangan.php?action=edit&id=<?= e($row['Id_Tunjangan']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="tunjangan.php?action=delete&id=<?= e($row['Id_Tunjangan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Data Tunjangan</h2>
        <form method="POST" action="tunjangan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Tunjangan" value="<?= e($tunjangan_data['Id_Tunjangan'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Nama_Tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Nama Tunjangan</label>
                <input type="text" id="Nama_Tunjangan" name="Nama_Tunjangan" value="<?= e($tunjangan_data['Nama_Tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="Jumlah_Tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Jumlah Tunjangan (Rp)</label>
                <input type="number" id="Jumlah_Tunjangan" name="Jumlah_Tunjangan" value="<?= e($tunjangan_data['Jumlah_Tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>

            <div class="mb-8">
                <label for="Keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                <textarea id="Keterangan" name="Keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?= e($tunjangan_data['Keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="tunjangan.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Potongan';

// ... (Seluruh Logika PHP untuk tambah, edit, hapus tetap di sini) ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... Logika POST ...
}
if ($action === 'delete' && $id) {
    // ... Logika Hapus ...
}

$potongan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Potongan';
    $stmt = $conn->prepare("SELECT * FROM potongan WHERE id_potongan = ?");
    $stmt->bind_param("s", $id);
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
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Potongan</h2>
            <a href="potongan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold">Tambah Potongan</a>
        </div>
        <div class="overflow-x-auto mt-6">
            <table class="w-full text-sm text-center text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 border">No</th>
                        <th class="px-4 py-3 border">Nama Potongan</th>
                        <th class="px-4 py-3 border">Tarif</th>
                        <th class="px-4 py-3 border">Keterangan</th>
                        <th class="px-4 py-3 border">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect(); // Buka koneksi lagi hanya untuk view
                    $result = $conn->query("SELECT * FROM potongan ORDER BY nama_potongan ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 border"><?= $no++ ?></td>
                        <td class="px-4 py-3 border text-left"><?= e($row['nama_potongan']) ?></td>
                        <td class="px-4 py-3 border"><?= e($row['tarif']) ?><?= strpos(strtolower($row['nama_potongan']), 'bpjs') !== false ? '%' : '' ?></td>
                        <td class="px-4 py-3 border text-left"><?= e($row['keterangan']) ?></td>
                        <td class="px-4 py-3 border space-x-2">
                            <a href="potongan.php?action=edit&id=<?= e($row['id_potongan']) ?>" class="bg-blue-500 text-white text-xs px-3 py-1 rounded hover:bg-blue-600">Edit</a>
                            <a href="potongan.php?action=delete&id=<?= e($row['id_potongan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs px-3 py-1 rounded hover:bg-red-600" onclick="return confirm('Yakin?')">Hapus</a>
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
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6"><?= e(ucfirst($action)) ?> Data Potongan</h2>
        <form method="POST" action="potongan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_potongan" value="<?= e($potongan_data['id_potongan'] ?? '') ?>">
            
            <div class="mb-4">
                <label for="nama_potongan" class="block mb-2 text-sm font-bold text-gray-700">Nama Potongan</label>
                <input type="text" id="nama_potongan" name="nama_potongan" value="<?= e($potongan_data['nama_potongan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500" required>
            </div>
            
            <div class="mb-4">
                <label for="tarif" class="block mb-2 text-sm font-bold text-gray-700">Tarif</label>
                <input type="number" step="0.01" id="tarif" name="tarif" value="<?= e($potongan_data['tarif'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500" required>
                <p class="text-xs text-gray-500 mt-1">Gunakan format desimal (misal: 2.5 untuk 2.5%).</p>
            </div>

            <div class="mb-6">
                <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500"><?= e($potongan_data['keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="potongan.php?action=list" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
// Memanggil footer.php
require_once __DIR__ . '/../includes/footer.php';
?>
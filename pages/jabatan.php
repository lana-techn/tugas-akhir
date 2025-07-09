<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Jabatan';

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        set_flash_message('error', 'Token keamanan tidak valid.');
    } else {
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM karyawan WHERE id_jabatan = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $count = $stmt_check->get_result()->fetch_row()[0];
        $stmt_check->close();

        if ($count > 0) {
            set_flash_message('error', 'Jabatan tidak bisa dihapus karena masih digunakan oleh karyawan.');
        } else {
            $stmt = $conn->prepare("DELETE FROM jabatan WHERE id_jabatan = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                set_flash_message('success', 'Data jabatan berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data jabatan.');
            }
            $stmt->close();
        }
    }
    header('Location: jabatan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_jabatan = $_POST['id_jabatan'] ?? null;
    $nama_jabatan = trim($_POST['nama_jabatan'] ?? '');
    $pendidikan = $_POST['pendidikan'] ?? '';

    if (empty($nama_jabatan) || empty($pendidikan)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_jabatan) { // Edit
            $stmt = $conn->prepare("UPDATE jabatan SET nama_jabatan = ?, pendidikan = ? WHERE id_jabatan = ?");
            $stmt->bind_param("sss", $nama_jabatan, $pendidikan, $id_jabatan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $nama_clean = preg_replace('/\s+/', '', $nama_jabatan);
            $prefix = strtoupper(substr($nama_clean, 0, 2));
            $stmt_cek = $conn->prepare("SELECT id_jabatan FROM jabatan WHERE id_jabatan LIKE ? ORDER BY id_jabatan DESC LIMIT 1");
            $prefix_like = $prefix . '%';
            $stmt_cek->bind_param("s", $prefix_like);
            $stmt_cek->execute();
            $result_cek = $stmt_cek->get_result();
            $last_id_num = ($row = $result_cek->fetch_assoc()) ? intval(substr($row['id_jabatan'], 2)) : 0;
            $id_jabatan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO jabatan (id_jabatan, nama_jabatan, pendidikan) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $id_jabatan_new, $nama_jabatan, $pendidikan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Jabatan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data jabatan.");
        
        $stmt->close();
        header('Location: jabatan.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$jabatan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Jabatan';
    $stmt = $conn->prepare("SELECT * FROM jabatan WHERE id_jabatan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $jabatan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$jabatan_data) {
        set_flash_message('error', 'Data jabatan tidak ditemukan.');
        header('Location: jabatan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Jabatan';
}

generate_csrf_token();
$conn->close();

// 2. MEMANGGIL TAMPILAN (VIEW)
// =======================================
// Asumsi: header.php berada di folder ../includes/
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Jabatan</h2>
            <a href="jabatan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Jabatan
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Jabatan</th>
                        <th class="px-4 py-3">Pendidikan Minimal</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['pendidikan']) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="jabatan.php?action=edit&id=<?= e($row['id_jabatan']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="jabatan.php?action=delete&id=<?= e($row['id_jabatan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
         <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Jabatan</h2>
        <form method="POST" action="jabatan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_jabatan" value="<?= e($jabatan_data['id_jabatan'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="nama_jabatan" class="block mb-2 text-sm font-bold text-gray-700">Nama Jabatan</label>
                <input type="text" id="nama_jabatan" name="nama_jabatan" value="<?= e($jabatan_data['nama_jabatan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-8">
                <label for="pendidikan" class="block mb-2 text-sm font-bold text-gray-700">Pendidikan Minimal</label>
                <select id="pendidikan" name="pendidikan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="">-- Pilih Pendidikan --</option>
                    <?php $pendidikan_opts = ['SMA/SMK', 'D3', 'S1', 'S2']; ?>
                    <?php foreach ($pendidikan_opts as $p): ?>
                        <option value="<?= e($p) ?>" <?= (isset($jabatan_data) && $jabatan_data['pendidikan'] == $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="jabatan.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php
// Asumsi: footer.php berada di folder ../includes/
require_once __DIR__ . '/../includes/footer.php';
?>
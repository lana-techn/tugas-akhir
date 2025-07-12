<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Jabatan';

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Cek apakah jabatan masih digunakan oleh karyawan
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM KARYAWAN WHERE Id_Jabatan = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $count = $stmt_check->get_result()->fetch_row()[0];
        $stmt_check->close();

        if ($count > 0) {
            set_flash_message('error', 'Jabatan tidak bisa dihapus karena masih digunakan oleh karyawan.');
        } else {
            $stmt = $conn->prepare("DELETE FROM JABATAN WHERE Id_Jabatan = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) set_flash_message('success', 'Data jabatan berhasil dihapus.');
            else set_flash_message('error', 'Gagal menghapus data jabatan.');
            $stmt->close();
        }
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: jabatan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_jabatan = $_POST['Id_Jabatan'] ?? null;
    $nama_jabatan = trim($_POST['Nama_Jabatan'] ?? '');
    $pendidikan = $_POST['Pendidikan'] ?? '';

    if (empty($nama_jabatan) || empty($pendidikan)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_jabatan) { // Edit
            $stmt = $conn->prepare("UPDATE JABATAN SET Nama_Jabatan = ?, Pendidikan = ? WHERE Id_Jabatan = ?");
            $stmt->bind_param("sss", $nama_jabatan, $pendidikan, $id_jabatan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $nama_clean = preg_replace('/\s+/', '', $nama_jabatan);
            $prefix = strtoupper(substr($nama_clean, 0, 2));
            
            $stmt_cek = $conn->prepare("SELECT Id_Jabatan FROM JABATAN WHERE Id_Jabatan LIKE ? ORDER BY Id_Jabatan DESC LIMIT 1");
            $prefix_like = $prefix . '%';
            $stmt_cek->bind_param("s", $prefix_like);
            $stmt_cek->execute();
            $result_cek = $stmt_cek->get_result();
            $last_id_num = ($row = $result_cek->fetch_assoc()) ? intval(substr($row['Id_Jabatan'], strlen($prefix))) : 0;
            $id_jabatan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO JABATAN (Id_Jabatan, Nama_Jabatan, Pendidikan) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $id_jabatan_new, $nama_jabatan, $pendidikan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Jabatan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data jabatan: " . $stmt->error);
        
        $stmt->close();
        header('Location: jabatan.php?action=list');
        exit;
    }
}

$jabatan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Jabatan';
    $stmt = $conn->prepare("SELECT * FROM JABATAN WHERE Id_Jabatan = ?");
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

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . '/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Jabatan</h2>
                <p class="text-gray-500 text-sm">Kelola semua jabatan yang tersedia di perusahaan.</p>
            </div>
            <a href="jabatan.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Jabatan
            </a>
        </div>
        
        <form method="get" action="jabatan.php" class="mb-6">
            <input type="hidden" name="action" value="list">
            <div class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama jabatan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID Jabatan</th>
                        <th class="px-6 py-3">Nama Jabatan</th>
                        <th class="px-6 py-3">Pendidikan Minimal</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data dengan pagination dan search
                    $count_sql = "SELECT COUNT(Id_Jabatan) as total FROM JABATAN WHERE Nama_Jabatan LIKE ?";
                    $stmt_count = $conn->prepare($count_sql);
                    $search_param = "%" . $search . "%";
                    $stmt_count->bind_param("s", $search_param);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    $sql = "SELECT * FROM JABATAN WHERE Nama_Jabatan LIKE ? ORDER BY Nama_Jabatan ASC LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Jabatan']) ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Jabatan']) ?></td>
                            <td class="px-6 py-4"><?= e($row['Pendidikan']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="jabatan.php?action=edit&id=<?= e($row['Id_Jabatan']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="jabatan.php?action=delete&id=<?= e($row['Id_Jabatan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile;
                    } else {
                        echo '<tr><td colspan="4" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        // Menampilkan pagination
        echo generate_pagination_links($page, $total_pages, 'jabatan.php', ['action' => 'list', 'search' => $search]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= $action === 'add' ? 'Tambah' : 'Edit' ?> Jabatan</h2>
        <p class="text-center text-gray-500 mb-8">Isi detail jabatan pada form di bawah ini.</p>
        <form method="POST" action="jabatan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Jabatan" value="<?= e($jabatan_data['Id_Jabatan'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Nama_Jabatan" class="block mb-2 text-sm font-medium text-gray-700">Nama Jabatan</label>
                <input type="text" id="Nama_Jabatan" name="Nama_Jabatan" value="<?= e($jabatan_data['Nama_Jabatan'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-8">
                <label for="Pendidikan" class="block mb-2 text-sm font-medium text-gray-700">Pendidikan Minimal</label>
                <select id="Pendidikan" name="Pendidikan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="">-- Pilih Pendidikan --</option>
                    <?php $pendidikan_opts = ['SMA/SMK', 'D3', 'S1', 'S2']; ?>
                    <?php foreach ($pendidikan_opts as $p): ?>
                        <option value="<?= e($p) ?>" <?= (isset($jabatan_data) && $jabatan_data['Pendidikan'] == $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="jabatan.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
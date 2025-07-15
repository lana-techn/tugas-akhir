<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . 
'/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Lembur';

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM LEMBUR WHERE Id_Lembur = ?");
        $stmt->bind_param("i", $id);
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
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_lembur = $_POST['Id_Lembur'] ?? null;
    $nama_lembur = trim($_POST['Nama_Lembur']);
    $upah_lembur_per_jam = filter_input(INPUT_POST, 'Upah_Lembur_Per_Jam', FILTER_VALIDATE_INT); // Changed field name
    $keterangan = trim($_POST['Keterangan']);

    if (empty($nama_lembur) || $upah_lembur_per_jam === false) {
        set_flash_message('error', 'Nama lembur dan upah lembur per jam wajib diisi dengan format yang benar.');
    } else {
        if ($id_lembur) { // Edit
            $stmt = $conn->prepare("UPDATE LEMBUR SET Nama_Lembur = ?, Upah_Lembur_Per_Jam = ?, Keterangan = ? WHERE Id_Lembur = ?"); // Changed field name
            $stmt->bind_param("sisi", $nama_lembur, $upah_lembur_per_jam, $keterangan, $id_lembur);
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt = $conn->prepare("INSERT INTO LEMBUR (Nama_Lembur, Upah_Lembur_Per_Jam, Keterangan) VALUES (?, ?, ?)"); // Changed field name
            $stmt->bind_param("sis", $nama_lembur, $upah_lembur_per_jam, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data lembur berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data lembur: " . $stmt->error);
        }
        $stmt->close();
        header('Location: lembur.php?action=list');
        exit;
    }
}

$lembur_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Lembur';
    $stmt = $conn->prepare("SELECT * FROM LEMBUR WHERE Id_Lembur = ?");
    $stmt->bind_param("i", $id);
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

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . 
'/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Upah Lembur</h2>
                <p class="text-gray-500 text-sm">Kelola tarif upah lembur per jam.</p>
            </div>
            <a href="lembur.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Data Lembur
            </a>
        </div>
        
        <form method="get" action="lembur.php" class="mb-6">
            <input type="hidden" name="action" value="list">
            <div class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama/jenis lembur..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">Nama/Jenis Lembur</th>
                        <th class="px-6 py-3 text-right">Upah per Jam</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count_sql = "SELECT COUNT(Id_Lembur) as total FROM LEMBUR WHERE Nama_Lembur LIKE ?";
                    $stmt_count = $conn->prepare($count_sql);
                    $search_param = "%" . $search . "%";
                    $stmt_count->bind_param("s", $search_param);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    $sql = "SELECT * FROM LEMBUR WHERE Nama_Lembur LIKE ? ORDER BY Nama_Lembur ASC LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Lembur']) ?></td>
                            <td class="px-6 py-4 text-right font-semibold text-green-700">Rp <?= number_format($row['Upah_Lembur_Per_Jam'] ?? 0, 0, ',', '.') ?></td> <!-- Changed field name -->
                            <td class="px-6 py-4 text-gray-500"><?= e($row['Keterangan']) ?: '-' ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="lembur.php?action=edit&id=<?= e($row['Id_Lembur']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="lembur.php?action=delete&id=<?= e($row['Id_Lembur']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile;
                    } else {
                        echo '<tr><td colspan="4" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    $stmt->close(); $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php echo generate_pagination_links($page, $total_pages, 'lembur.php', ['action' => 'list', 'search' => $search]); ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Data Lembur</h2>
        <p class="text-center text-gray-500 mb-8">Isi detail tarif lembur pada form di bawah ini.</p>
        <form method="POST" action="lembur.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Lembur" value="<?= e($lembur_data['Id_Lembur'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Nama_Lembur" class="block mb-2 text-sm font-medium text-gray-700">Nama/Jenis Lembur</label>
                <input type="text" id="Nama_Lembur" name="Nama_Lembur" value="<?= e($lembur_data['Nama_Lembur'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="Upah_Lembur_Per_Jam" class="block mb-2 text-sm font-medium text-gray-700">Upah Lembur per Jam (Rp)</label> <!-- Changed label -->
                <input type="number" id="Upah_Lembur_Per_Jam" name="Upah_Lembur_Per_Jam" value="<?= e($lembur_data['Upah_Lembur_Per_Jam'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required min="0"> <!-- Changed name and id -->
            </div>

            <div class="mb-8">
                <label for="Keterangan" class="block mb-2 text-sm font-medium text-gray-700">Keterangan</label>
                <textarea id="Keterangan" name="Keterangan" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><?= e($lembur_data['Keterangan'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="lembur.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . 
'/../includes/footer.php';
?>
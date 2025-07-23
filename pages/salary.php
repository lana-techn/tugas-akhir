<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Gaji Pokok';

// Logika Pagination & Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_jabatan = $_GET['search_jabatan'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil data jabatan untuk form
$jabatan_list = $conn->query("SELECT Id_Jabatan, Nama_Jabatan FROM JABATAN ORDER BY Nama_Jabatan")->fetch_all(MYSQLI_ASSOC);

// --- PROSES HAPUS GAJI POKOK ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM GAJI_POKOK WHERE Id_Gapok = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji pokok berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data gaji pokok.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: salary.php');
    exit;
}

// --- PROSES TAMBAH & EDIT GAJI POKOK---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        header('Location: salary.php');
        exit;
    }
}

$gapok_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Gaji Pokok';
    $stmt = $conn->prepare("SELECT * FROM GAJI_POKOK WHERE Id_Gapok = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $gapok_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($action === 'Tambah') {
    $page_title = 'Tambah Gaji Pokok';
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Gaji Pokok</h2>
            <p class="text-gray-500 text-sm">Kelola nominal gaji pokok berdasarkan jabatan dan masa kerja.</p>
        </div>
        <a href="salary.php?action=Tambah" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Gaji Pokok
        </a>
    </div>

    <?php display_flash_message(); ?>

    <form method="get" action="salary.php" class="mb-6">
        <div class="relative">
            <input type="text" name="search_jabatan" value="<?= e($search_jabatan) ?>" placeholder="Cari berdasarkan nama jabatan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-search text-gray-400"></i>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-6 py-3">Nama Jabatan</th>
                    <th class="px-6 py-3 text-center">Masa Kerja (Tahun)</th>
                    <th class="px-6 py-3 text-right">Nominal</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count_sql = "SELECT COUNT(gp.Id_Gapok) as total FROM GAJI_POKOK gp JOIN JABATAN j ON gp.Id_Jabatan = j.Id_Jabatan WHERE j.Nama_Jabatan LIKE ?";
                $stmt_count = $conn->prepare($count_sql);
                $search_param = "%" . $search_jabatan . "%";
                $stmt_count->bind_param("s", $search_param);
                $stmt_count->execute();
                $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total_records / $records_per_page);
                $stmt_count->close();
                
                $sql = "SELECT gp.*, j.Nama_Jabatan FROM GAJI_POKOK gp JOIN JABATAN j ON gp.Id_Jabatan = j.Id_Jabatan WHERE j.Nama_Jabatan LIKE ? ORDER BY j.Nama_Jabatan, gp.Masa_Kerja ASC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()): ?>
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Jabatan']) ?></td>
                        <td class="px-6 py-4 text-center"><?= e($row['Masa_Kerja']) ?></td>
                        <td class="px-6 py-4 text-right font-semibold text-green-700">Rp <?= number_format($row['Nominal'] ?? 0, 0, ',', '.') ?></td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-4">
                                <a href="salary.php?action=edit&id=<?= e($row['Id_Gapok']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="salary.php?action=delete&id=<?= e($row['Id_Gapok']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;
                } else {
                    echo '<tr><td colspan="4" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                }
                $stmt->close();
                ?>
            </tbody>
        </table>
    </div>
    <?php echo generate_pagination_links($page, $total_pages, 'salary.php', ['search_jabatan' => $search_jabatan]); ?>
</div>
<?php endif; ?>

<?php if ($action === 'Tambah' || $action === 'edit'): ?>
<div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= $action === 'Tambah' ? 'Tambah' : 'Edit' ?> Gaji Pokok</h2>
    <p class="text-center text-gray-500 mb-8">Isi detail gaji pokok pada form di bawah ini.</p>
    <form method="POST" action="salary.php">
        <?php csrf_input(); ?>
        <input type="hidden" name="Id_Gapok" value="<?= e($gapok_data['Id_Gapok'] ?? '') ?>">
        
        <div class="space-y-5">
            <div>
                <label for="Id_Jabatan" class="block mb-2 text-sm font-medium text-gray-700">Jabatan</label>
                <select id="Id_Jabatan" name="Id_Jabatan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="">- Pilih Jabatan -</option>
                    <?php foreach($jabatan_list as $jabatan): ?>
                    <option value="<?= e($jabatan['Id_Jabatan']) ?>" <?= (isset($gapok_data) && $gapok_data['Id_Jabatan'] == $jabatan['Id_Jabatan']) ? 'selected' : '' ?>><?= e($jabatan['Nama_Jabatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="Masa_Kerja" class="block mb-2 text-sm font-medium text-gray-700">Masa Kerja (Tahun)</label>
                <input type="number" id="Masa_Kerja" name="Masa_Kerja" value="<?= e($gapok_data['Masa_Kerja'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required min="0">
            </div>
            <div>
                <label for="Nominal" class="block mb-2 text-sm font-medium text-gray-700">Nominal Gaji Pokok (Rp)</label>
                <input type="number" id="Nominal" name="Nominal" value="<?= e($gapok_data['Nominal'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required min="0">
            </div>
        </div>
        
        <div class="flex items-center justify-end space-x-4 mt-8">
            <a href="salary.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
            <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
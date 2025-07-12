<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); // Menggunakan fungsi yang sudah diperbarui

// Menggunakan koneksi PDO
$database = new Database();
$conn = $database->getConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Pengguna';

// Logika Pagination & Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$level_filter = $_GET['level'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        if ($id == $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        } else {
            // Menggunakan PDO prepared statements
            $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM KARYAWAN WHERE Id_Pengguna = :id");
            $stmt_check->bindParam(':id', $id);
            $stmt_check->execute();
            $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];

            if ($count > 0) {
                set_flash_message('error', 'Pengguna tidak bisa dihapus karena terikat dengan data karyawan.');
            } else {
                $stmt = $conn->prepare("DELETE FROM PENGGUNA WHERE Id_Pengguna = :id");
                $stmt->bindParam(':id', $id);
                if ($stmt->execute()) {
                    set_flash_message('success', 'Data pengguna berhasil dihapus.');
                } else {
                    set_flash_message('error', 'Gagal menghapus data pengguna.');
                }
            }
        }
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: pengguna.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_pengguna = $_POST['Id_Pengguna'] ?? null;
    $email = filter_var(trim($_POST['Email']), FILTER_VALIDATE_EMAIL);
    $level = $_POST['Level'];
    $password = $_POST['Password'];

    if (empty($email) || empty($level)) {
        set_flash_message('error', 'Email dan Level wajib diisi dengan format yang benar.');
    } else {
        if ($id_pengguna) { // Edit
            $stmt = $conn->prepare("UPDATE PENGGUNA SET Email = :email, Level = :level WHERE Id_Pengguna = :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':level', $level);
            $stmt->bindParam(':id', $id_pengguna);
            $action_text = 'diperbarui';
            
            if (!empty($password)) {
                $stmt_pass = $conn->prepare("UPDATE PENGGUNA SET Password = :password WHERE Id_Pengguna = :id");
                $stmt_pass->bindParam(':password', $password); // Sebaiknya di-hash
                $stmt_pass->bindParam(':id', $id_pengguna);
                $stmt_pass->execute();
            }
        } else { // Tambah
            if (empty($password)) {
                set_flash_message('error', 'Password wajib diisi untuk pengguna baru.');
                header('Location: pengguna.php?action=add');
                exit;
            }
            
            $stmt_cek = $conn->prepare("SELECT Id_Pengguna FROM PENGGUNA WHERE Email = :email");
            $stmt_cek->bindParam(':email', $email);
            $stmt_cek->execute();
            if ($stmt_cek->rowCount() > 0) {
                 set_flash_message('error', 'Email sudah terdaftar. Gunakan email lain.');
                 header('Location: pengguna.php?action=add');
                 exit;
            }

            $prefix_map = ['Admin' => 'ADM', 'Pemilik' => 'PEM', 'Karyawan' => 'KAR'];
            $prefix = $prefix_map[$level] ?? 'USR';
            
            $stmt_last = $conn->prepare("SELECT Id_Pengguna FROM PENGGUNA WHERE Id_Pengguna LIKE :prefix ORDER BY Id_Pengguna DESC LIMIT 1");
            $like_prefix = $prefix . '%';
            $stmt_last->bindParam(':prefix', $like_prefix);
            $stmt_last->execute();
            $result = $stmt_last->fetch(PDO::FETCH_ASSOC);
            $last_id_num = $result ? intval(substr($result['Id_Pengguna'], 3)) : 0;
            $id_pengguna_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO PENGGUNA (Id_Pengguna, Email, Level, Password) VALUES (:id, :email, :level, :password)");
            $stmt->bindParam(':id', $id_pengguna_new);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':level', $level);
            $stmt->bindParam(':password', $password); // Sebaiknya di-hash
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Pengguna berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data pengguna.");
        }
        
        header('Location: pengguna.php?action=list');
        exit;
    }
}

$pengguna_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Pengguna';
    // Gunakan koneksi mysqli sementara untuk get_result, atau ubah cara fetch data
    $conn_mysqli = db_connect();
    $stmt_mysqli = $conn_mysqli->prepare("SELECT * FROM PENGGUNA WHERE Id_Pengguna = ?");
    $stmt_mysqli->bind_param("s", $id);
    $stmt_mysqli->execute();
    $pengguna_data = $stmt_mysqli->get_result()->fetch_assoc();
    $stmt_mysqli->close();
    if (!$pengguna_data) {
        set_flash_message('error', 'Data pengguna tidak ditemukan.');
        header('Location: pengguna.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Pengguna';
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
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Pengguna</h2>
                <p class="text-gray-500 text-sm">Kelola akses dan peran pengguna sistem.</p>
            </div>
            <a href="pengguna.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Pengguna
            </a>
        </div>
        
        <form method="get" action="pengguna.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari email pengguna..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div>
                <select name="level" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Level</option>
                    <option value="Admin" <?= $level_filter == 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Pemilik" <?= $level_filter == 'Pemilik' ? 'selected' : '' ?>>Pemilik</option>
                    <option value="Karyawan" <?= $level_filter == 'Karyawan' ? 'selected' : '' ?>>Karyawan</option>
                </select>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Level</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build query dinamis
                    $count_sql = "SELECT COUNT(Id_Pengguna) as total FROM PENGGUNA WHERE Email LIKE :search";
                    $search_param = "%" . $search . "%";
                    $params = [':search' => $search_param];

                    if ($level_filter) {
                        $count_sql .= " AND Level = :level";
                        $params[':level'] = $level_filter;
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    $stmt_count->execute($params);
                    $total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
                    $total_pages = ceil($total_records / $records_per_page);

                    $sql = "SELECT * FROM PENGGUNA WHERE Email LIKE :search";
                    if ($level_filter) {
                        $sql .= " AND Level = :level";
                    }
                    $sql .= " ORDER BY Level, Email ASC LIMIT :limit OFFSET :offset";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':search', $search_param);
                    if ($level_filter) {
                        $stmt->bindValue(':level', $level_filter);
                    }
                    $stmt->bindValue(':limit', (int) $records_per_page, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
                    $stmt->execute();
    
                    if($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Pengguna']) ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Email']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                <?= $row['Level'] === 'Admin' ? 'bg-indigo-100 text-indigo-800' : '' ?>
                                <?= $row['Level'] === 'Pemilik' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $row['Level'] === 'Karyawan' ? 'bg-green-100 text-green-800' : '' ?>
                            ">
                                <?= e(ucfirst($row['Level'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-4">
                                <a href="pengguna.php?action=edit&id=<?= e($row['Id_Pengguna']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <?php if ($row['Id_Pengguna'] !== $_SESSION['user_id']): ?>
                                <a href="pengguna.php?action=delete&id=<?= e($row['Id_Pengguna']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="4" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    // TIDAK ADA $stmt->close() ATAU $conn->close() DI SINI
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        echo generate_pagination_links($page, $total_pages, ['action' => 'list', 'search' => $search, 'level' => $level_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Pengguna</h2>
        <p class="text-center text-gray-500 mb-8">Isi detail dan peran pengguna baru.</p>
        <form method="POST" action="pengguna.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Pengguna" value="<?= e($pengguna_data['Id_Pengguna'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Email" class="block mb-2 text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="Email" name="Email" value="<?= e($pengguna_data['Email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="Level" class="block mb-2 text-sm font-medium text-gray-700">Level</label>
                <select id="Level" name="Level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="Admin" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="Pemilik" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Pemilik') ? 'selected' : '' ?>>Pemilik</option>
                    <option value="Karyawan" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Karyawan') ? 'selected' : '' ?>>Karyawan</option>
                </select>
            </div>
            
            <div class="mb-8">
                <label for="Password" class="block mb-2 text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="Password" name="Password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" <?= ($action === 'add') ? 'required' : '' ?>>
                <?php if ($action === 'edit'): ?>
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="pengguna.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
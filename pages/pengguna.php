<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Pengguna';

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        if ($id == $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        } else {
            $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                set_flash_message('success', 'Data pengguna berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data pengguna.');
            }
            $stmt->close();
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

    $id_pengguna = $_POST['id_pengguna'] ?? null;
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $level = $_POST['level'];
    $password = $_POST['password'];

    if (empty($email) || empty($level)) {
        set_flash_message('error', 'Email dan Level wajib diisi dengan format yang benar.');
    } else {
        if ($id_pengguna) { // Edit
            $stmt = $conn->prepare("UPDATE pengguna SET email = ?, level = ? WHERE id_pengguna = ?");
            $stmt->bind_param("sss", $email, $level, $id_pengguna);
            $action_text = 'diperbarui';
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
                $stmt_pass->bind_param("ss", $hashed_password, $id_pengguna);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
        } else { // Tambah
            if (empty($password)) {
                set_flash_message('error', 'Password wajib diisi untuk pengguna baru.');
                header('Location: pengguna.php?action=add');
                exit;
            }
            
            $stmt_cek = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
            $stmt_cek->bind_param("s", $email);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                 set_flash_message('error', 'Email sudah terdaftar. Gunakan email lain.');
                 header('Location: pengguna.php?action=add');
                 exit;
            }
            $stmt_cek->close();

            $prefix = strtolower(substr($level, 0, 1));
            $result = $conn->query("SELECT id_pengguna FROM pengguna WHERE id_pengguna LIKE '{$prefix}%' ORDER BY id_pengguna DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_pengguna'], 1)) : 0;
            $id_pengguna_new = $prefix . str_pad($last_id_num + 1, 2, '0', STR_PAD_LEFT);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO pengguna (id_pengguna, email, level, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_pengguna_new, $email, $level, $hashed_password);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Pengguna berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data pengguna: " . $stmt->error);
        
        $stmt->close();
        header('Location: pengguna.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$pengguna_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Pengguna';
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $pengguna_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pengguna_data) {
        set_flash_message('error', 'Data pengguna tidak ditemukan.');
        header('Location: pengguna.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Pengguna';
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
            <h2 class="text-2xl font-bold text-gray-800">Daftar Pengguna Sistem</h2>
            <a href="pengguna.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Pengguna
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM pengguna ORDER BY level, email ASC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['email']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                <?= $row['level'] === 'admin' ? 'bg-indigo-100 text-indigo-800' : '' ?>
                                <?= $row['level'] === 'pemilik' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $row['level'] === 'karyawan' ? 'bg-green-100 text-green-800' : '' ?>
                            ">
                                <?= e(ucfirst($row['level'])) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="pengguna.php?action=edit&id=<?= e($row['id_pengguna']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <?php if ($row['id_pengguna'] !== $_SESSION['user_id']): ?>
                            <a href="pengguna.php?action=delete&id=<?= e($row['id_pengguna']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus pengguna ini?')">Hapus</a>
                            <?php endif; ?>
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
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Pengguna</h2>
        <form method="POST" action="pengguna.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_pengguna" value="<?= e($pengguna_data['id_pengguna'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="email" class="block mb-2 text-sm font-bold text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?= e($pengguna_data['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="level" class="block mb-2 text-sm font-bold text-gray-700">Level</label>
                <select id="level" name="level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="admin" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="pemilik" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'pemilik') ? 'selected' : '' ?>>Pemilik</option>
                    <option value="karyawan" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                </select>
            </div>
            
            <div class="mb-8">
                <label for="password" class="block mb-2 text-sm font-bold text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md" <?= ($action === 'add') ? 'required' : '' ?>>
                <?php if ($action === 'edit'): ?>
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-end space-x-4">
                <a href="pengguna.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
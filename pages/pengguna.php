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
            // Cek apakah pengguna terkait dengan data karyawan
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM KARYAWAN WHERE Id_Pengguna = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            $count = $stmt_check->get_result()->fetch_row()[0];
            $stmt_check->close();

            if ($count > 0) {
                set_flash_message('error', 'Pengguna tidak bisa dihapus karena terikat dengan data karyawan.');
            } else {
                $stmt = $conn->prepare("DELETE FROM PENGGUNA WHERE Id_Pengguna = ?");
                $stmt->bind_param("s", $id);
                if ($stmt->execute()) set_flash_message('success', 'Data pengguna berhasil dihapus.');
                else set_flash_message('error', 'Gagal menghapus data pengguna.');
                $stmt->close();
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
            $stmt = $conn->prepare("UPDATE PENGGUNA SET Email = ?, Level = ? WHERE Id_Pengguna = ?");
            $stmt->bind_param("sss", $email, $level, $id_pengguna);
            $action_text = 'diperbarui';
            
            // Hanya update password jika diisi
            if (!empty($password)) {
                $stmt_pass = $conn->prepare("UPDATE PENGGUNA SET Password = ? WHERE Id_Pengguna = ?");
                // Di dunia nyata, gunakan hashing. Untuk proyek ini, kita simpan teks biasa.
                $stmt_pass->bind_param("ss", $password, $id_pengguna);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
        } else { // Tambah
            if (empty($password)) {
                set_flash_message('error', 'Password wajib diisi untuk pengguna baru.');
                header('Location: pengguna.php?action=add');
                exit;
            }
            
            $stmt_cek = $conn->prepare("SELECT Id_Pengguna FROM PENGGUNA WHERE Email = ?");
            $stmt_cek->bind_param("s", $email);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                 set_flash_message('error', 'Email sudah terdaftar. Gunakan email lain.');
                 header('Location: pengguna.php?action=add');
                 exit;
            }
            $stmt_cek->close();

            $prefix_map = ['Admin' => 'ADM', 'Pemilik' => 'PEM', 'Karyawan' => 'KAR'];
            $prefix = $prefix_map[$level] ?? 'USR';
            
            $result = $conn->query("SELECT Id_Pengguna FROM PENGGUNA WHERE Id_Pengguna LIKE '{$prefix}%' ORDER BY Id_Pengguna DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['Id_Pengguna'], 3)) : 0;
            $id_pengguna_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO PENGGUNA (Id_Pengguna, Email, Level, Password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_pengguna_new, $email, $level, $password);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Pengguna berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data pengguna: " . $stmt->error);
        
        $stmt->close();
        header('Location: pengguna.php?action=list');
        exit;
    }
}

$pengguna_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Pengguna';
    $stmt = $conn->prepare("SELECT * FROM PENGGUNA WHERE Id_Pengguna = ?");
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
// sidebar.php tidak perlu dipanggil lagi karena sudah ada di header.php

?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Pengguna Sistem</h2>
            <a href="pengguna.php?action=add" class="w-full sm:w-auto bg-green-600 text-white text-center px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Pengguna
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT * FROM PENGGUNA ORDER BY Level, Email ASC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($row['Id_Pengguna']) ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Email']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                <?= $row['Level'] === 'Admin' ? 'bg-indigo-100 text-indigo-800' : '' ?>
                                <?= $row['Level'] === 'Pemilik' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $row['Level'] === 'Karyawan' ? 'bg-green-100 text-green-800' : '' ?>
                            ">
                                <?= e(ucfirst($row['Level'])) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="pengguna.php?action=edit&id=<?= e($row['Id_Pengguna']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <?php if ($row['Id_Pengguna'] !== $_SESSION['user_id']): ?>
                            <a href="pengguna.php?action=delete&id=<?= e($row['Id_Pengguna']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus pengguna ini?')">Hapus</a>
                            <?php endif; ?>
                        </div>
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
            <input type="hidden" name="Id_Pengguna" value="<?= e($pengguna_data['Id_Pengguna'] ?? '') ?>">
            
            <div class="mb-5">
                <label for="Email" class="block mb-2 text-sm font-bold text-gray-700">Email</label>
                <input type="email" id="Email" name="Email" value="<?= e($pengguna_data['Email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div class="mb-5">
                <label for="Level" class="block mb-2 text-sm font-bold text-gray-700">Level</label>
                <select id="Level" name="Level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="Admin" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="Pemilik" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Pemilik') ? 'selected' : '' ?>>Pemilik</option>
                    <option value="Karyawan" <?= (isset($pengguna_data) && $pengguna_data['Level'] == 'Karyawan') ? 'selected' : '' ?>>Karyawan</option>
                </select>
            </div>
            
            <div class="mb-8">
                <label for="Password" class="block mb-2 text-sm font-bold text-gray-700">Password</label>
                <input type="password" id="Password" name="Password" class="w-full px-3 py-2 border border-gray-300 rounded-md" <?= ($action === 'add') ? 'required' : '' ?>>
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
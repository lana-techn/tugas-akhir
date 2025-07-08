<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Pengguna';

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Mencegah admin menghapus akunnya sendiri
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

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_pengguna = $_POST['id_pengguna'] ?? null;
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $level = $_POST['level'];
    $password = $_POST['password'];

    if (empty($email) || empty($level)) {
        set_flash_message('error', 'Email dan Level wajib diisi dengan format yang benar.');
    } else {
        if ($id_pengguna) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE pengguna SET email = ?, level = ? WHERE id_pengguna = ?");
            $stmt->bind_param("sss", $email, $level, $id_pengguna);
            $action_text = 'diperbarui';
            if ($password) { // Jika password diisi saat edit, update juga passwordnya
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
                $stmt_pass->bind_param("ss", $hashed_password, $id_pengguna);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
        } else { // --- Proses TAMBAH ---
            if (empty($password)) {
                set_flash_message('error', 'Password wajib diisi untuk pengguna baru.');
                header('Location: pengguna.php?action=add');
                exit;
            }
            
            // Cek email duplikat
            $stmt_cek = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
            $stmt_cek->bind_param("s", $email);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                 set_flash_message('error', 'Email sudah terdaftar. Gunakan email lain.');
                 header('Location: pengguna.php?action=add');
                 exit;
            }
            $stmt_cek->close();

            // Generate ID
            $prefix = strtolower(substr($level, 0, 1));
            $result = $conn->query("SELECT id_pengguna FROM pengguna WHERE id_pengguna LIKE '{$prefix}%' ORDER BY id_pengguna DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_pengguna'], 1)) : 0;
            $id_pengguna_new = $prefix . str_pad($last_id_num + 1, 2, '0', STR_PAD_LEFT);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // HASH PASSWORD BARU
            $stmt = $conn->prepare("INSERT INTO pengguna (id_pengguna, email, level, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_pengguna_new, $email, $level, $hashed_password);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Pengguna berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data pengguna.");
        }
        $stmt->close();
        header('Location: pengguna.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .notif { padding: 12px 15px; background: white; border-radius: 5px; font-size: 15px; color: #444; border-left-width: 5px; margin-bottom: 20px; }
        .notif-success { border-left-color: #43a047; }
        .notif-error { border-left-color: #e53935; }
    </style>
</head>
<body class="bg-[#e8f5e9] font-['Segoe_UI',_sans-serif]">
<div class="flex min-h-screen">
    <div class="w-64 flex-shrink-0 bg-gradient-to-b from-[#b2f2bb] to-white text-black">
        <div class="p-5 bg-[#98eba3] text-center"><h3 class="text-xl font-bold">ADMIN</h3></div>
        <nav class="mt-4">
            <a href="../index.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-home w-6 text-center"></i> <span class="ml-2">Dashboard</span></a>
            <a href="pengguna.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-user-shield w-6 text-center"></i> <span class="ml-2">Pengguna</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR PENGGUNA</h2>
                    <a href="pengguna.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Pengguna</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3 border">NO</th>
                                <th class="px-4 py-3 border">EMAIL</th>
                                <th class="px-4 py-3 border">LEVEL</th>
                                <th class="px-4 py-3 border">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM pengguna ORDER BY email ASC");
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b">
                                <td class="px-4 py-3 border"><?= $no++ ?></td>
                                <td class="px-4 py-3 border text-left"><?= e($row['email']) ?></td>
                                <td class="px-4 py-3 border"><?= e(ucfirst($row['level'])) ?></td>
                                <td class="px-4 py-3 border">
                                    <a href="pengguna.php?action=edit&id=<?= e($row['id_pengguna']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <?php if ($row['id_pengguna'] !== $_SESSION['user_id']): ?>
                                    <a href="pengguna.php?action=delete&id=<?= e($row['id_pengguna']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus pengguna ini?')">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Pengguna</h2>
                <form method="POST" action="pengguna.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_pengguna" value="<?= e($pengguna_data['id_pengguna'] ?? '') ?>">
                    
                    <div class="mb-4">
                        <label for="email" class="block mb-2 text-sm font-bold text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($pengguna_data['email'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="level" class="block mb-2 text-sm font-bold text-gray-700">Level</label>
                        <select id="level" name="level" class="w-full px-3 py-2 border rounded-md" required>
                            <option value="admin" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="pemilik" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'pemilik') ? 'selected' : '' ?>>Pemilik</option>
                            <option value="karyawan" <?= (isset($pengguna_data) && $pengguna_data['level'] == 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block mb-2 text-sm font-bold text-gray-700">Password</label>
                        <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded-md" <?= ($action === 'add') ? 'required' : '' ?>>
                        <?php if ($action === 'edit'): ?>
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4">
                        <a href="pengguna.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
                        <button type="submit" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] font-bold text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
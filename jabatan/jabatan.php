<?php
// 1. SETUP & ROUTING
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default: menampilkan daftar
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Jabatan';

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    // Validasi CSRF Token dari URL
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        set_flash_message('error', 'Token keamanan tidak valid.');
    } else {
        // Cek apakah jabatan masih digunakan
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM karyawan WHERE id_jabatan = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $count = $result_check->fetch_row()[0];
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

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_jabatan = $_POST['id_jabatan'] ?? null;
    $nama_jabatan = trim($_POST['nama_jabatan'] ?? '');
    $pendidikan = $_POST['pendidikan'] ?? '';

    // Validasi
    if (empty($nama_jabatan) || empty($pendidikan)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_jabatan) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE jabatan SET nama_jabatan = ?, pendidikan = ? WHERE id_jabatan = ?");
            $stmt->bind_param("sss", $nama_jabatan, $pendidikan, $id_jabatan);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            $nama_clean = preg_replace('/\s+/', '', $nama_jabatan);
            $prefix = strtoupper(substr($nama_clean, 0, 2));
            $stmt_cek = $conn->prepare("SELECT id_jabatan FROM jabatan WHERE id_jabatan LIKE ? ORDER BY id_jabatan DESC LIMIT 1");
            $prefix_like = $prefix . '%';
            $stmt_cek->bind_param("s", $prefix_like);
            $stmt_cek->execute();
            $result_cek = $stmt_cek->get_result();
            $last_id_num = 0;
            if ($row = $result_cek->fetch_assoc()) {
                $last_id_num = intval(substr($row['id_jabatan'], 2));
            }
            $id_jabatan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO jabatan (id_jabatan, nama_jabatan, pendidikan) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $id_jabatan_new, $nama_jabatan, $pendidikan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Jabatan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data jabatan.");
        }
        $stmt->close();
        header('Location: jabatan.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$jabatan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Jabatan';
    $stmt = $conn->prepare("SELECT * FROM jabatan WHERE id_jabatan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $jabatan_data = $result->fetch_assoc();
    $stmt->close();
    if (!$jabatan_data) {
        set_flash_message('error', 'Data jabatan tidak ditemukan.');
        header('Location: jabatan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Jabatan';
}

generate_csrf_token(); // Generate token untuk form dan link hapus
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
        <div class="p-5 bg-[#98eba3] text-center">
            <h3 class="text-xl font-bold">ADMIN</h3>
        </div>
        <nav class="mt-4">
            <a href="../index.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200">
                <i class="fas fa-home w-6 text-center"></i> <span class="ml-2">Dashboard</span>
            </a>
             <a href="jabatan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 bg-[#388e3c] text-white">
                <i class="fas fa-briefcase w-6 text-center"></i> <span class="ml-2">Jabatan</span>
            </a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 mt-4">
                <i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span>
            </a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR JABATAN</h2>
                    <a href="jabatan.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Jabatan</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3 border border-gray-300">NO</th>
                                <th class="px-4 py-3 border border-gray-300">NAMA JABATAN</th>
                                <th class="px-4 py-3 border border-gray-300">PENDIDIKAN</th>
                                <th class="px-4 py-3 border border-gray-300">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC");
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b border-gray-300">
                                <td class="px-4 py-3 border border-gray-300"><?= $no++ ?></td>
                                <td class="px-4 py-3 border border-gray-300 text-left"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3 border border-gray-300"><?= e($row['pendidikan']) ?></td>
                                <td class="px-4 py-3 border border-gray-300">
                                    <a href="jabatan.php?action=edit&id=<?= e($row['id_jabatan']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <a href="jabatan.php?action=delete&id=<?= e($row['id_jabatan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Jabatan</h2>
                <form method="POST" action="jabatan.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_jabatan" value="<?= e($jabatan_data['id_jabatan'] ?? '') ?>">
                    
                    <div class="mb-4">
                        <label for="nama_jabatan" class="block mb-2 text-sm font-bold text-gray-700">Nama Jabatan</label>
                        <input type="text" id="nama_jabatan" name="nama_jabatan" value="<?= e($jabatan_data['nama_jabatan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="pendidikan" class="block mb-2 text-sm font-bold text-gray-700">Pendidikan</label>
                        <select id="pendidikan" name="pendidikan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                            <option value="">-- Pilih Pendidikan --</option>
                            <?php $pendidikan_opts = ['SMA/SMK', 'D3', 'S1', 'S2']; ?>
                            <?php foreach ($pendidikan_opts as $p): ?>
                                <option value="<?= e($p) ?>" <?= (isset($jabatan_data) && $jabatan_data['pendidikan'] == $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4">
                        <a href="jabatan.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
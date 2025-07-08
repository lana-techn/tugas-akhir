<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Tunjangan';


// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM tunjangan WHERE id_tunjangan = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data tunjangan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data tunjangan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: tunjangan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_tunjangan = $_POST['id_tunjangan'] ?? null;
    $nama_tunjangan = trim($_POST['nama_tunjangan']);
    $jumlah = filter_input(INPUT_POST, 'jumlah_tunjangan', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama_tunjangan) || $jumlah === false) {
        set_flash_message('error', 'Nama dan jumlah tunjangan wajib diisi dengan format yang benar.');
    } else {
        if ($id_tunjangan) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE tunjangan SET nama_tunjangan = ?, jumlah_tunjangan = ?, keterangan = ? WHERE id_tunjangan = ?");
            $stmt->bind_param("siss", $nama_tunjangan, $jumlah, $keterangan, $id_tunjangan);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            $result = $conn->query("SELECT id_tunjangan FROM tunjangan ORDER BY id_tunjangan DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_tunjangan'], 2)) : 0;
            $id_tunjangan_new = 'TJ' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO tunjangan (id_tunjangan, nama_tunjangan, jumlah_tunjangan, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $id_tunjangan_new, $nama_tunjangan, $jumlah, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data tunjangan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data tunjangan.");
        }
        $stmt->close();
        header('Location: tunjangan.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$tunjangan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Tunjangan';
    $stmt = $conn->prepare("SELECT * FROM tunjangan WHERE id_tunjangan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $tunjangan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tunjangan_data) {
        set_flash_message('error', 'Data tunjangan tidak ditemukan.');
        header('Location: tunjangan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Data Tunjangan';
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
            <a href="tunjangan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-gift w-6 text-center"></i> <span class="ml-2">Tunjangan</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR TUNJANGAN</h2>
                    <a href="tunjangan.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Tunjangan</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3 border">NAMA TUNJANGAN</th>
                                <th class="px-4 py-3 border">JUMLAH</th>
                                <th class="px-4 py-3 border">KETERANGAN</th>
                                <th class="px-4 py-3 border">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM tunjangan ORDER BY nama_tunjangan ASC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b">
                                <td class="px-4 py-3 border text-left"><?= e($row['nama_tunjangan']) ?></td>
                                <td class="px-4 py-3 border">Rp <?= number_format($row['jumlah_tunjangan'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 border text-left"><?= e($row['keterangan']) ?></td>
                                <td class="px-4 py-3 border">
                                    <a href="tunjangan.php?action=edit&id=<?= e($row['id_tunjangan']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded">Edit</a>
                                    <a href="tunjangan.php?action=delete&id=<?= e($row['id_tunjangan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded" onclick="return confirm('Yakin?')">Hapus</a>
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
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Data Tunjangan</h2>
                <form method="POST" action="tunjangan.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_tunjangan" value="<?= e($tunjangan_data['id_tunjangan'] ?? '') ?>">
                    
                    <div class="mb-4">
                        <label for="nama_tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Nama Tunjangan</label>
                        <input type="text" id="nama_tunjangan" name="nama_tunjangan" value="<?= e($tunjangan_data['nama_tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md" required>
                    </div>

                    
                    <div class="mb-4">
                        <label for="jumlah_tunjangan" class="block mb-2 text-sm font-bold text-gray-700">Jumlah Tunjangan (Rp)</label>
                        <input type="number" id="jumlah_tunjangan" name="jumlah_tunjangan" value="<?= e($tunjangan_data['jumlah_tunjangan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md" required>
                    </div>

                    <div class="mb-6">
                        <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border rounded-md"><?= e($tunjangan_data['keterangan'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4">
                        <a href="tunjangan.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
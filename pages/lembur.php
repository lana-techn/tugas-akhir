<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default: menampilkan daftar
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Lembur';

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM lembur WHERE id_lembur = ?");
        $stmt->bind_param("s", $id);
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

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_lembur = $_POST['id_lembur'] ?? null;
    $nama_lembur = trim($_POST['nama_lembur']);
    $lama_lembur = filter_input(INPUT_POST, 'lama_lembur', FILTER_VALIDATE_INT);
    $upah_lembur = filter_input(INPUT_POST, 'upah_lembur', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama_lembur) || $lama_lembur === false || $upah_lembur === false) {
        set_flash_message('error', 'Nama, lama lembur, dan upah lembur wajib diisi dengan format yang benar.');
    } else {
        if ($id_lembur) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE lembur SET nama_lembur = ?, lama_lembur = ?, upah_lembur = ?, keterangan = ? WHERE id_lembur = ?");
            $stmt->bind_param("siiss", $nama_lembur, $lama_lembur, $upah_lembur, $keterangan, $id_lembur);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            $result = $conn->query("SELECT id_lembur FROM lembur ORDER BY id_lembur DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_lembur'], 1)) : 0;
            $id_lembur_new = 'L' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO lembur (id_lembur, nama_lembur, lama_lembur, upah_lembur, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $id_lembur_new, $nama_lembur, $lama_lembur, $upah_lembur, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data lembur berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data lembur.");
        }
        $stmt->close();
        header('Location: lembur.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$lembur_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Lembur';
    $stmt = $conn->prepare("SELECT * FROM lembur WHERE id_lembur = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lembur_data = $result->fetch_assoc();
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
            <a href="lembur.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-clock w-6 text-center"></i> <span class="ml-2">Lembur</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR LEMBUR</h2>
                    <a href="lembur.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Lembur</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3 border border-gray-300">NO</th>
                                <th class="px-4 py-3 border border-gray-300">NAMA LEMBUR</th>
                                <th class="px-4 py-3 border border-gray-300">LAMA (/JAM)</th>
                                <th class="px-4 py-3 border border-gray-300">UPAH</th>
                                <th class="px-4 py-3 border border-gray-300">KETERANGAN</th>
                                <th class="px-4 py-3 border border-gray-300">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM lembur ORDER BY nama_lembur ASC");
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b border-gray-300">
                                <td class="px-4 py-3 border border-gray-300"><?= $no++ ?></td>
                                <td class="px-4 py-3 border border-gray-300 text-left"><?= e($row['nama_lembur']) ?></td>
                                <td class="px-4 py-3 border border-gray-300"><?= e($row['lama_lembur']) ?></td>
                                <td class="px-4 py-3 border border-gray-300">Rp <?= number_format($row['upah_lembur'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 border border-gray-300 text-left"><?= e($row['keterangan']) ?></td>
                                <td class="px-4 py-3 border border-gray-300">
                                    <a href="lembur.php?action=edit&id=<?= e($row['id_lembur']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <a href="lembur.php?action=delete&id=<?= e($row['id_lembur']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Data Lembur</h2>
                <form method="POST" action="lembur.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_lembur" value="<?= e($lembur_data['id_lembur'] ?? '') ?>">
                    
                    <div class="mb-4">
                        <label for="nama_lembur" class="block mb-2 text-sm font-bold text-gray-700">Nama Lembur</label>
                        <input type="text" id="nama_lembur" name="nama_lembur" value="<?= e($lembur_data['nama_lembur'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="lama_lembur" class="block mb-2 text-sm font-bold text-gray-700">Lama Lembur (/Jam)</label>
                        <input type="number" id="lama_lembur" name="lama_lembur" value="<?= e($lembur_data['lama_lembur'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>

                    <div class="mb-4">
                        <label for="upah_lembur" class="block mb-2 text-sm font-bold text-gray-700">Upah Lembur per Jam (Rp)</label>
                        <input type="number" id="upah_lembur" name="upah_lembur" value="<?= e($lembur_data['upah_lembur'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>

                    <div class="mb-6">
                        <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= e($lembur_data['keterangan'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4">
                        <a href="lembur.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
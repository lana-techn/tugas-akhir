<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Potongan';

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM potongan WHERE id_potongan = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data potongan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data potongan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: potongan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

    $id_potongan = $_POST['id_potongan'] ?? null;
    $nama_potongan = trim($_POST['nama_potongan']);
    $tarif = filter_input(INPUT_POST, 'tarif', FILTER_VALIDATE_FLOAT);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama_potongan) || $tarif === false) {
        set_flash_message('error', 'Nama potongan dan tarif wajib diisi dengan format yang benar.');
    } else {
        if ($id_potongan) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE potongan SET nama_potongan = ?, tarif = ?, keterangan = ? WHERE id_potongan = ?");
            $stmt->bind_param("sdss", $nama_potongan, $tarif, $keterangan, $id_potongan);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            $result = $conn->query("SELECT id_potongan FROM potongan ORDER BY id_potongan DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_potongan'], 1)) : 0;
            $id_potongan_new = 'P' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO potongan (id_potongan, nama_potongan, tarif, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $id_potongan_new, $nama_potongan, $tarif, $keterangan);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data potongan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data potongan.");
        }
        $stmt->close();
        header('Location: potongan.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$potongan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Potongan';
    $stmt = $conn->prepare("SELECT * FROM potongan WHERE id_potongan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $potongan_data = $result->fetch_assoc();
    $stmt->close();
    if (!$potongan_data) {
        set_flash_message('error', 'Data potongan tidak ditemukan.');
        header('Location: potongan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Data Potongan';
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
            <a href="potongan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-cut w-6 text-center"></i> <span class="ml-2">Potongan</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR POTONGAN</h2>
                    <a href="potongan.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Potongan</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3 border">NO</th>
                                <th class="px-4 py-3 border">NAMA POTONGAN</th>
                                <th class="px-4 py-3 border">TARIF</th>
                                <th class="px-4 py-3 border">KETERANGAN</th>
                                <th class="px-4 py-3 border">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM potongan ORDER BY nama_potongan ASC");
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b">
                                <td class="px-4 py-3 border"><?= $no++ ?></td>
                                <td class="px-4 py-3 border text-left"><?= e($row['nama_potongan']) ?></td>
                                <td class="px-4 py-3 border"><?= e($row['tarif']) ?><?= strpos($row['nama_potongan'], 'BPJS') !== false ? '%' : '' ?></td>
                                <td class="px-4 py-3 border text-left"><?= e($row['keterangan']) ?></td>
                                <td class="px-4 py-3 border">
                                    <a href="potongan.php?action=edit&id=<?= e($row['id_potongan']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <a href="potongan.php?action=delete&id=<?= e($row['id_potongan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Data Potongan</h2>
                <form method="POST" action="potongan.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_potongan" value="<?= e($potongan_data['id_potongan'] ?? '') ?>">
                    
                    <div class="mb-4">
                        <label for="nama_potongan" class="block mb-2 text-sm font-bold text-gray-700">Nama Potongan</label>
                        <input type="text" id="nama_potongan" name="nama_potongan" value="<?= e($potongan_data['nama_potongan'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tarif" class="block mb-2 text-sm font-bold text-gray-700">Tarif</label>
                        <input type="number" id="tarif" name="tarif" step="0.01" value="<?= e($potongan_data['tarif'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md" required>
                        <p class="text-xs text-gray-500 mt-1">Gunakan format desimal (misal: 2.5 untuk 2.5%).</p>
                    </div>

                    <div class="mb-6">
                        <label for="keterangan" class="block mb-2 text-sm font-bold text-gray-700">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-3 py-2 border rounded-md"><?= e($potongan_data['keterangan'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4">
                        <a href="potongan.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
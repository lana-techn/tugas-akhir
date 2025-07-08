<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Presensi';

// Ambil data untuk dropdown dan filter
$karyawan_list = $conn->query("SELECT id_karyawan, nama_karyawan FROM karyawan ORDER BY nama_karyawan ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM presensi WHERE id_presensi = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data presensi berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data presensi.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: presensi.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_presensi = $_POST['id_presensi'] ?? null;
    $id_karyawan = $_POST['id_karyawan'];
    $bulan = $_POST['bulan'];
    $tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);
    $hadir = filter_input(INPUT_POST, 'hadir', FILTER_VALIDATE_INT);
    $sakit = filter_input(INPUT_POST, 'sakit', FILTER_VALIDATE_INT);
    $izin = filter_input(INPUT_POST, 'izin', FILTER_VALIDATE_INT);
    $alpha = filter_input(INPUT_POST, 'alpha', FILTER_VALIDATE_INT);

    if (empty($id_karyawan) || empty($bulan) || $tahun === false || $hadir === false || $sakit === false || $izin === false || $alpha === false) {
        set_flash_message('error', 'Semua kolom wajib diisi dengan format yang benar.');
    } else {
        if ($id_presensi) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE presensi SET id_karyawan=?, bulan=?, tahun=?, hadir=?, sakit=?, izin=?, alpha=? WHERE id_presensi=?");
            $stmt->bind_param("ssiiiis", $id_karyawan, $bulan, $tahun, $hadir, $sakit, $izin, $alpha, $id_presensi);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            // Cek duplikat data presensi untuk karyawan, bulan, dan tahun yang sama
            $stmt_cek = $conn->prepare("SELECT id_presensi FROM presensi WHERE id_karyawan = ? AND bulan = ? AND tahun = ?");
            $stmt_cek->bind_param("ssi", $id_karyawan, $bulan, $tahun);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                set_flash_message('error', "Presensi untuk karyawan ini di bulan dan tahun yang sama sudah ada.");
                header('Location: presensi.php?action=add');
                exit;
            }
            $stmt_cek->close();

            $result = $conn->query("SELECT id_presensi FROM presensi ORDER BY id_presensi DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_presensi'], 2)) : 0;
            $id_presensi_new = 'PR' . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO presensi (id_presensi, id_karyawan, bulan, tahun, hadir, sakit, izin, alpha) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiiiii", $id_presensi_new, $id_karyawan, $bulan, $tahun, $hadir, $sakit, $izin, $alpha);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data presensi berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data presensi.");
        
        $stmt->close();
        header('Location: presensi.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$presensi_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Presensi';
    $stmt = $conn->prepare("SELECT * FROM presensi WHERE id_presensi = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $presensi_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$presensi_data) {
        set_flash_message('error', 'Data presensi tidak ditemukan.');
        header('Location: presensi.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Presensi';
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
        .form-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1.5rem; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</head>
<body class="bg-[#e8f5e9] font-['Segoe_UI',_sans-serif]">
<div class="flex min-h-screen">
    <div class="w-64 flex-shrink-0 bg-gradient-to-b from-[#b2f2bb] to-white text-black">
        <div class="p-5 bg-[#98eba3] text-center"><h3 class="text-xl font-bold">ADMIN</h3></div>
        <nav class="mt-4">
            <a href="../index.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white"><i class="fas fa-home w-6 text-center"></i> <span class="ml-2">Dashboard</span></a>
            <a href="presensi.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-user-check w-6 text-center"></i> <span class="ml-2">Presensi</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR PRESENSI</h2>
                    <a href="presensi.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Presensi</a>
                </div>

                <form method="get" action="presensi.php" class="mt-6 p-4 bg-gray-50 rounded-lg flex items-center space-x-4">
                    <input type="hidden" name="action" value="list">
                    <select name="nama" class="w-full sm:w-1/3 px-3 py-2 border rounded-md text-sm">
                        <option value="">-- Semua Karyawan --</option>
                        <?php foreach($karyawan_list as $k): ?>
                            <option value="<?= e($k['nama_karyawan']) ?>" <?= ($_GET['nama'] ?? '') == $k['nama_karyawan'] ? 'selected' : '' ?>><?= e($k['nama_karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="tahun" placeholder="Tahun (e.g. 2024)" value="<?= e($_GET['tahun'] ?? '') ?>" class="w-full sm:w-1/4 px-3 py-2 border rounded-md text-sm">
                    <button type="submit" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm">Tampilkan</button>
                    <a href="presensi.php?action=list" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm">Reset</a>
                </form>

                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th class="px-4 py-3">NAMA KARYAWAN</th>
                                <th class="px-4 py-3">PERIODE</th>
                                <th class="px-2 py-3">H</th>
                                <th class="px-2 py-3">S</th>
                                <th class="px-2 py-3">I</th>
                                <th class="px-2 py-3">A</th>
                                <th class="px-4 py-3">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $params = [];
                            $types = '';
                            $query = "SELECT p.*, k.nama_karyawan FROM presensi p JOIN karyawan k ON p.id_karyawan = k.id_karyawan WHERE 1=1";
                            if (!empty($_GET['nama'])) {
                                $query .= " AND k.nama_karyawan = ?";
                                $params[] = $_GET['nama'];
                                $types .= 's';
                            }
                            if (!empty($_GET['tahun'])) {
                                $query .= " AND p.tahun = ?";
                                $params[] = $_GET['tahun'];
                                $types .= 'i';
                            }
                            $query .= " ORDER BY p.tahun DESC, FIELD(p.bulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari'), k.nama_karyawan ASC";
                            
                            $stmt_list = $conn->prepare($query);
                            if (!empty($params)) $stmt_list->bind_param($types, ...$params);
                            $stmt_list->execute();
                            $result = $stmt_list->get_result();

                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b">
                                <td class="px-4 py-3 font-medium text-left"><?= e($row['nama_karyawan']) ?></td>
                                <td class="px-4 py-3"><?= e($row['bulan']) ?> <?= e($row['tahun']) ?></td>
                                <td class="px-2 py-3 font-bold text-green-600"><?= e($row['hadir']) ?></td>
                                <td class="px-2 py-3 font-bold text-yellow-600"><?= e($row['sakit']) ?></td>
                                <td class="px-2 py-3 font-bold text-blue-600"><?= e($row['izin']) ?></td>
                                <td class="px-2 py-3 font-bold text-red-600"><?= e($row['alpha']) ?></td>
                                <td class="px-4 py-3">
                                    <a href="presensi.php?action=edit&id=<?= e($row['id_presensi']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded">Edit</a>
                                    <a href="presensi.php?action=delete&id=<?= e($row['id_presensi']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded" onclick="return confirm('Yakin?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; $stmt_list->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Presensi</h2>
                <form method="POST" action="presensi.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_presensi" value="<?= e($presensi_data['id_presensi'] ?? '') ?>">
                    
                    <div class="form-grid">
                        <div>
                            <div class="mb-4">
                                <label for="id_karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                                <select name="id_karyawan" id="id_karyawan" class="w-full px-3 py-2 border rounded-md" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                                    <option value="">- Pilih Karyawan -</option>
                                    <?php foreach($karyawan_list as $k): ?>
                                    <option value="<?= e($k['id_karyawan']) ?>" <?= (isset($presensi_data) && $presensi_data['id_karyawan'] == $k['id_karyawan']) ? 'selected' : '' ?>><?= e($k['nama_karyawan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if($action === 'edit'): ?>
                                <input type="hidden" name="id_karyawan" value="<?= e($presensi_data['id_karyawan']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label for="bulan" class="block mb-2 text-sm font-bold text-gray-700">Bulan</label>
                                <select name="bulan" id="bulan" class="w-full px-3 py-2 border rounded-md" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                                    <?php foreach($bulan_list as $b): ?>
                                    <option value="<?= e($b) ?>" <?= (isset($presensi_data) && $presensi_data['bulan'] == $b) ? 'selected' : '' ?>><?= e($b) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                 <?php if($action === 'edit'): ?>
                                <input type="hidden" name="bulan" value="<?= e($presensi_data['bulan']) ?>">
                                <?php endif; ?>
                            </div>
                             <div class="mb-4">
                                <label for="tahun" class="block mb-2 text-sm font-bold text-gray-700">Tahun</label>
                                <input type="number" name="tahun" id="tahun" value="<?= e($presensi_data['tahun'] ?? date('Y')) ?>" class="w-full px-3 py-2 border rounded-md" required <?= $action === 'edit' ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div>
                            <div class="mb-4">
                                <label for="hadir" class="block mb-2 text-sm font-bold text-gray-700">Hadir</label>
                                <input type="number" id="hadir" name="hadir" value="<?= e($presensi_data['hadir'] ?? '0') ?>" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="sakit" class="block mb-2 text-sm font-bold text-gray-700">Sakit</label>
                                <input type="number" id="sakit" name="sakit" value="<?= e($presensi_data['sakit'] ?? '0') ?>" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="izin" class="block mb-2 text-sm font-bold text-gray-700">Izin</label>
                                <input type="number" id="izin" name="izin" value="<?= e($presensi_data['izin'] ?? '0') ?>" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                             <div class="mb-4">
                                <label for="alpha" class="block mb-2 text-sm font-bold text-gray-700">Alpha</label>
                                <input type="number" id="alpha" name="alpha" value="<?= e($presensi_data['alpha'] ?? '0') ?>" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-4 mt-6">
                        <a href="presensi.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
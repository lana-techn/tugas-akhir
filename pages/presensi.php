<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Presensi';

// Ambil data untuk dropdown dan filter
$karyawan_list = $conn->query("SELECT Id_Karyawan, Nama_Karyawan FROM KARYAWAN ORDER BY Nama_Karyawan ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM PRESENSI WHERE Id_Presensi = ?");
        $stmt->bind_param("i", $id); // Id_Presensi adalah INT
        if ($stmt->execute()) set_flash_message('success', 'Data presensi berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data presensi.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: presensi.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_presensi = $_POST['Id_Presensi'] ?? null;
    $id_karyawan = $_POST['Id_Karyawan'];
    $bulan = $_POST['Bulan'];
    $tahun = filter_input(INPUT_POST, 'Tahun', FILTER_VALIDATE_INT);
    $hadir = filter_input(INPUT_POST, 'Hadir', FILTER_VALIDATE_INT);
    $sakit = filter_input(INPUT_POST, 'Sakit', FILTER_VALIDATE_INT);
    $izin = filter_input(INPUT_POST, 'Izin', FILTER_VALIDATE_INT);
    $alpha = filter_input(INPUT_POST, 'Alpha', FILTER_VALIDATE_INT);

    if (empty($id_karyawan) || empty($bulan) || $tahun === false || $hadir === false || $sakit === false || $izin === false || $alpha === false) {
        set_flash_message('error', 'Semua kolom wajib diisi dengan format yang benar.');
    } else {
        if ($id_presensi) { // Edit
            $stmt = $conn->prepare("UPDATE PRESENSI SET Hadir=?, Sakit=?, Izin=?, Alpha=? WHERE Id_Presensi=?");
            $stmt->bind_param("iiiii", $hadir, $sakit, $izin, $alpha, $id_presensi);
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt_cek = $conn->prepare("SELECT Id_Presensi FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
            $stmt_cek->bind_param("ssi", $id_karyawan, $bulan, $tahun);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                set_flash_message('error', "Presensi untuk karyawan ini di bulan dan tahun yang sama sudah ada.");
                header('Location: presensi.php?action=add');
                exit;
            }
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO PRESENSI (Id_Karyawan, Bulan, Tahun, Hadir, Sakit, Izin, Alpha) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiii", $id_karyawan, $bulan, $tahun, $hadir, $sakit, $izin, $alpha);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data presensi berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data presensi: " . $stmt->error);
        
        $stmt->close();
        header('Location: presensi.php?action=list');
        exit;
    }
}

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$presensi_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Presensi';
    $stmt = $conn->prepare("SELECT * FROM PRESENSI WHERE Id_Presensi = ?");
    $stmt->bind_param("i", $id);
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
$conn->close();

// 2. MEMANGGIL TAMPILAN (VIEW)
// =======================================
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Presensi Karyawan</h2>
            <a href="presensi.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm w-full md:w-auto text-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Presensi
            </a>
        </div>
        
        <!-- Filter Data -->
        <form method="get" action="presensi.php" class="mb-6 p-4 bg-gray-50 rounded-lg border">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <input type="hidden" name="action" value="list">
                <div>
                    <label for="filter_nama" class="block mb-2 text-sm font-medium text-gray-700">Nama Karyawan</label>
                    <select id="filter_nama" name="nama" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">-- Semua Karyawan --</option>
                        <?php foreach($karyawan_list as $k): ?>
                            <option value="<?= e($k['Nama_Karyawan']) ?>" <?= ($_GET['nama'] ?? '') == $k['Nama_Karyawan'] ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_tahun" class="block mb-2 text-sm font-medium text-gray-700">Tahun</label>
                    <input type="number" id="filter_tahun" name="tahun" placeholder="Cth: 2024" value="<?= e($_GET['tahun'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold">Tampilkan</button>
                    <a href="presensi.php?action=list" class="w-full text-center bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm font-semibold">Reset</a>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama Karyawan</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-2 py-3" title="Hadir">H</th>
                        <th class="px-2 py-3" title="Sakit">S</th>
                        <th class="px-2 py-3" title="Izin">I</th>
                        <th class="px-2 py-3" title="Alpha">A</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $params = [];
                    $types = '';
                    $query = "SELECT p.*, k.Nama_Karyawan FROM PRESENSI p JOIN KARYAWAN k ON p.Id_Karyawan = k.Id_Karyawan WHERE 1=1";
                    if (!empty($_GET['nama'])) {
                        $query .= " AND k.Nama_Karyawan = ?";
                        $params[] = $_GET['nama'];
                        $types .= 's';
                    }
                    if (!empty($_GET['tahun'])) {
                        $query .= " AND p.Tahun = ?";
                        $params[] = $_GET['tahun'];
                        $types .= 'i';
                    }
                    $query .= " ORDER BY p.Tahun DESC, FIELD(p.Bulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari'), k.Nama_Karyawan ASC";
                    
                    $stmt_list = $conn->prepare($query);
                    if (!empty($params)) $stmt_list->bind_param($types, ...$params);
                    $stmt_list->execute();
                    $result = $stmt_list->get_result();

                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-left text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['Bulan']) ?> <?= e($row['Tahun']) ?></td>
                        <td class="px-2 py-3 font-bold text-green-600"><?= e($row['Hadir']) ?></td>
                        <td class="px-2 py-3 font-bold text-yellow-600"><?= e($row['Sakit']) ?></td>
                        <td class="px-2 py-3 font-bold text-blue-600"><?= e($row['Izin']) ?></td>
                        <td class="px-2 py-3 font-bold text-red-600"><?= e($row['Alpha']) ?></td>
                        <td class="px-4 py-3 space-x-2">
                            <a href="presensi.php?action=edit&id=<?= e($row['Id_Presensi']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="presensi.php?action=delete&id=<?= e($row['Id_Presensi']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; $stmt_list->close(); $conn->close(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Data Presensi</h2>
        <form method="POST" action="presensi.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Presensi" value="<?= e($presensi_data['Id_Presensi'] ?? '') ?>">
            
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <!-- Kolom Kiri -->
                <div>
                    <label for="Id_Karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                    <select name="Id_Karyawan" id="Id_Karyawan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                        <option value="">- Pilih Karyawan -</option>
                        <?php foreach($karyawan_list as $k): ?>
                        <option value="<?= e($k['Id_Karyawan']) ?>" <?= (isset($presensi_data) && $presensi_data['Id_Karyawan'] == $k['Id_Karyawan']) ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($action === 'edit'): ?>
                    <input type="hidden" name="Id_Karyawan" value="<?= e($presensi_data['Id_Karyawan']) ?>">
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-x-4">
                    <div>
                        <label for="Bulan" class="block mb-2 text-sm font-bold text-gray-700">Bulan</label>
                        <select name="Bulan" id="Bulan" class="w-full px-3 py-2 border border-gray-300 rounded-md" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                            <?php foreach($bulan_list as $b): ?>
                            <option value="<?= e($b) ?>" <?= (isset($presensi_data) && $presensi_data['Bulan'] == $b) ? 'selected' : '' ?>><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                         <?php if($action === 'edit'): ?>
                        <input type="hidden" name="Bulan" value="<?= e($presensi_data['Bulan']) ?>">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="Tahun" class="block mb-2 text-sm font-bold text-gray-700">Tahun</label>
                        <input type="number" name="Tahun" id="Tahun" value="<?= e($presensi_data['Tahun'] ?? date('Y')) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required <?= $action === 'edit' ? 'readonly' : '' ?>>
                    </div>
                </div>
                <!-- Kolom Kanan -->
                <div class="grid grid-cols-2 gap-x-4">
                    <div>
                       <label for="Hadir" class="block mb-2 text-sm font-bold text-gray-700">Hadir</label>
                        <input type="number" id="Hadir" name="Hadir" value="<?= e($presensi_data['Hadir'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label for="Sakit" class="block mb-2 text-sm font-bold text-gray-700">Sakit</label>
                        <input type="number" id="Sakit" name="Sakit" value="<?= e($presensi_data['Sakit'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-x-4">
                     <div>
                        <label for="Izin" class="block mb-2 text-sm font-bold text-gray-700">Izin</label>
                        <input type="number" id="Izin" name="Izin" value="<?= e($presensi_data['Izin'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                     <div>
                        <label for="Alpha" class="block mb-2 text-sm font-bold text-gray-700">Alpha</label>
                        <input type="number" id="Alpha" name="Alpha" value="<?= e($presensi_data['Alpha'] ?? '0') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="presensi.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
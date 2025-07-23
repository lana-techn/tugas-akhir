<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . 
'/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Presensi';

// Logika Pagination & Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$id_karyawan_filter = $_GET['id_karyawan'] ?? '';
$tahun_filter = $_GET['tahun'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil data untuk dropdown dan filter
$karyawan_list = $conn->query("SELECT Id_Karyawan, Nama_Karyawan FROM KARYAWAN ORDER BY Nama_Karyawan ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM PRESENSI WHERE Id_Presensi = ?");
        $stmt->bind_param("i", $id);
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
    $hadir = filter_input(INPUT_POST, 'Hadir', FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]);
    $sakit = filter_input(INPUT_POST, 'Sakit', FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]);
    $izin = filter_input(INPUT_POST, 'Izin', FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]);
    $alpha = filter_input(INPUT_POST, 'Alpha', FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]);
    $jam_lembur = filter_input(INPUT_POST, 'Jam_Lembur', FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]); // New field

    if (empty($id_karyawan) || empty($bulan) || $tahun === false || $hadir === false || $sakit === false || $izin === false || $alpha === false || $jam_lembur === false) {
        set_flash_message('error', 'Semua kolom wajib diisi dengan format yang benar dan angka tidak boleh negatif.');
    } else {
        if ($id_presensi) { // Edit
            $stmt = $conn->prepare("UPDATE PRESENSI SET Hadir=?, Sakit=?, Izin=?, Alpha=?, Jam_Lembur=? WHERE Id_Presensi=?"); // Added Jam_Lembur
            $stmt->bind_param("iiiiii", $hadir, $sakit, $izin, $alpha, $jam_lembur, $id_presensi); // Added 'i' for Jam_Lembur
            $action_text = 'diperbarui';
        } else { // Tambah
            $stmt_cek = $conn->prepare("SELECT Id_Presensi FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
            $stmt_cek->bind_param("ssi", $id_karyawan, $bulan, $tahun);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                set_flash_message('error', "Presensi untuk karyawan ini di bulan dan tahun yang sama sudah ada.");
                header('Location: presensi.php?action=Tambah');
                exit;
            }
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO PRESENSI (Id_Karyawan, Bulan, Tahun, Hadir, Sakit, Izin, Alpha, Jam_Lembur) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"); // Added Jam_Lembur
            $stmt->bind_param("ssiiiiii", $id_karyawan, $bulan, $tahun, $hadir, $sakit, $izin, $alpha, $jam_lembur); // Added 'i' for Jam_Lembur
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data presensi berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data presensi: " . $stmt->error);
        
        $stmt->close();
        header('Location: presensi.php?action=list');
        exit;
    }
}

$presensi_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Presensi';
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
} elseif ($action === 'Tambah') {
    $page_title = 'Tambah Presensi';
}

generate_csrf_token();

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . 
'/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Presensi</h2>
                <p class="text-gray-500 text-sm">Kelola data kehadiran, sakit, izin, alpha, dan jam lembur karyawan.</p>
            </div>
            <a href="presensi.php?action=Tambah" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Presensi
            </a>
        </div>
        
        <form method="get" action="presensi.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div>
                <label for="id_karyawan_filter" class="sr-only">Nama Karyawan</label>
                <select id="id_karyawan_filter" name="id_karyawan" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Semua Karyawan --</option>
                    <?php foreach($karyawan_list as $k): ?>
                        <option value="<?= e($k['Id_Karyawan']) ?>" <?= $id_karyawan_filter == $k['Id_Karyawan'] ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_tahun" class="sr-only">Tahun</label>
                <input type="number" name="tahun" id="filter_tahun" placeholder="Filter Tahun..." value="<?= e($tahun_filter) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">Terapkan</button>
                <a href="presensi.php?action=list" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Nama Karyawan</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-2 py-3">Hadir</th>
                        <th class="px-2 py-3">Sakit</th>
                        <th class="px-2 py-3">Izin</th>
                        <th class="px-2 py-3">Alpha</th>
                        <th class="px-2 py-3">Jam Lembur</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build query dinamis
                    $count_params = [];
                    $types_string_count = '';
                    $count_sql = "SELECT COUNT(p.Id_Presensi) as total FROM PRESENSI p WHERE 1=1";
                    if ($id_karyawan_filter) {
                        $count_sql .= " AND p.Id_Karyawan = ?";
                        array_push($count_params, $id_karyawan_filter);
                        $types_string_count .= 's';
                    }
                    if ($tahun_filter) {
                        $count_sql .= " AND p.Tahun = ?";
                        array_push($count_params, $tahun_filter);
                        $types_string_count .= 's';
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    if($types_string_count) $stmt_count->bind_param($types_string_count, ...$count_params);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    $data_params = $count_params;
                    $types_string_data = $types_string_count;
                    $sql = "SELECT p.*, k.Nama_Karyawan FROM PRESENSI p JOIN KARYAWAN k ON p.Id_Karyawan = k.Id_Karyawan WHERE 1=1";
                    if ($id_karyawan_filter) $sql .= " AND p.Id_Karyawan = ?";
                    if ($tahun_filter) $sql .= " AND p.Tahun = ?";
                    $sql .= " ORDER BY p.Tahun DESC, FIELD(p.Bulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari'), k.Nama_Karyawan ASC LIMIT ? OFFSET ?";
                    array_push($data_params, $records_per_page, $offset);
                    $types_string_data .= 'ii';

                    $stmt = $conn->prepare($sql);
                    if($types_string_data) $stmt->bind_param($types_string_data, ...$data_params);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-left text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                            <td class="px-4 py-3"><?= e($row['Bulan']) ?> <?= e($row['Tahun']) ?></td>
                            <td class="px-2 py-3"><span class="font-bold text-green-600 bg-green-50 px-2 py-1 rounded"><?= e($row['Hadir']) ?></span></td>
                            <td class="px-2 py-3"><span class="font-bold text-yellow-600 bg-yellow-50 px-2 py-1 rounded"><?= e($row['Sakit']) ?></span></td>
                            <td class="px-2 py-3"><span class="font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded"><?= e($row['Izin']) ?></span></td>
                            <td class="px-2 py-3"><span class="font-bold text-red-600 bg-red-50 px-2 py-1 rounded"><?= e($row['Alpha']) ?></span></td>
                            <td class="px-2 py-3"><span class="font-bold text-purple-600 bg-purple-50 px-2 py-1 rounded"><?= e($row['Jam_Lembur'] ?? '0') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="presensi.php?action=edit&id=<?= e($row['Id_Presensi']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="presensi.php?action=delete&id=<?= e($row['Id_Presensi']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile;
                    else:
                        echo '<tr><td colspan="8" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    endif;
                    $stmt->close(); $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        echo generate_pagination_links($page, $total_pages, 'presensi.php', ['action' => 'list', 'id_karyawan' => $id_karyawan_filter, 'tahun' => $tahun_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'Tambah' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Data Presensi</h2>
        <p class="text-center text-gray-500 mb-8">Masukkan data kehadiran untuk karyawan.</p>
        <form method="POST" action="presensi.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Presensi" value="<?= e($presensi_data['Id_Presensi'] ?? '') ?>">
            
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="md:col-span-2">
                    <label for="Id_Karyawan" class="block mb-2 text-sm font-medium text-gray-700">Nama Karyawan</label>
                    <select name="Id_Karyawan" id="Id_Karyawan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                        <option value="">- Pilih Karyawan -</option>
                        <?php foreach($karyawan_list as $k): ?>
                        <option value="<?= e($k['Id_Karyawan']) ?>" <?= (isset($presensi_data) && $presensi_data['Id_Karyawan'] == $k['Id_Karyawan']) ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($action === 'edit'): ?>
                    <input type="hidden" name="Id_Karyawan" value="<?= e($presensi_data['Id_Karyawan']) ?>">
                    <?php endif; ?>
                </div>
                <div>
                    <label for="Bulan" class="block mb-2 text-sm font-medium text-gray-700">Bulan</label>
                    <select name="Bulan" id="Bulan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required disabled>
                        <?php 
                        $current_month_numeric = date("n"); // Numeric month (1-12)
                        $current_month_name = $bulan_list[$current_month_numeric - 1]; // Get month name from array
                        foreach($bulan_list as $b): 
                        ?>
                        <option value="<?= e($b) ?>" <?= ($b == $current_month_name) ? 'selected' : '' ?>><?= e($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                     <input type="hidden" name="Bulan" value="<?= e($current_month_name) ?>">
                </div>
                <div>
                    <label for="Tahun" class="block mb-2 text-sm font-medium text-gray-700">Tahun</label>
                    <input type="number" name="Tahun" id="Tahun" value="<?= date("Y") ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required readonly>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:col-span-2">
                    <div>
                       <label for="Hadir" class="block mb-2 text-sm font-medium text-gray-700">Hadir</label>
                        <input type="number" id="Hadir" name="Hadir" value="<?= e($presensi_data['Hadir'] ?? '0') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required min="0">
                    </div>
                    <div>
                        <label for="Sakit" class="block mb-2 text-sm font-medium text-gray-700">Sakit</label>
                        <input type="number" id="Sakit" name="Sakit" value="<?= e($presensi_data['Sakit'] ?? '0') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" required min="0">
                    </div>
                     <div>
                        <label for="Izin" class="block mb-2 text-sm font-medium text-gray-700">Izin</label>
                        <input type="number" id="Izin" name="Izin" value="<?= e($presensi_data['Izin'] ?? '0') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required min="0">
                    </div>
                     <div>
                        <label for="Alpha" class="block mb-2 text-sm font-medium text-gray-700">Alpha</label>
                        <input type="number" id="Alpha" name="Alpha" value="<?= e($presensi_data['Alpha'] ?? '0') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" required min="0">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="Jam_Lembur" class="block mb-2 text-sm font-medium text-gray-700">Jam Lembur</label>
                    <input type="number" id="Jam_Lembur" name="Jam_Lembur" value="<?= e($presensi_data['Jam_Lembur'] ?? '0') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required min="0">
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="presensi.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . 
'/../includes/footer.php';
?>

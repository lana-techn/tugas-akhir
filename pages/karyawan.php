<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Karyawan';

// Logika Pagination & Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil data untuk dropdown forms
$jabatan_list = $conn->query("SELECT Id_Jabatan, Nama_Jabatan FROM JABATAN ORDER BY Nama_Jabatan")->fetch_all(MYSQLI_ASSOC);
$query_pengguna = "
    SELECT p.Id_Pengguna, p.Email 
    FROM PENGGUNA p
    LEFT JOIN KARYAWAN k ON p.Id_Pengguna = k.Id_Pengguna
    WHERE p.Level = 'Karyawan' AND k.Id_Karyawan IS NULL";
$pengguna_list = $conn->query($query_pengguna)->fetch_all(MYSQLI_ASSOC);

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM KARYAWAN WHERE Id_Karyawan = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data karyawan berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data karyawan.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: karyawan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_karyawan = $_POST['Id_Karyawan'] ?? null;
    $nama = trim($_POST['Nama_Karyawan']);
    $jk = $_POST['Jenis_Kelamin'];
    $telepon = trim($_POST['Telepon']);
    $alamat = trim($_POST['Alamat']);
    $tgl_lahir = $_POST['Tgl_Lahir'];
    $tgl_masuk = $_POST['Tgl_Awal_Kerja'];
    $id_pengguna = $_POST['Id_Pengguna'];
    $id_jabatan = $_POST['Id_Jabatan'];
    $status = $_POST['Status'];

    if (empty($nama) || empty($jk) || empty($telepon) || empty($alamat) || empty($tgl_lahir) || empty($tgl_masuk) || empty($id_pengguna) || empty($id_jabatan) || empty($status)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_karyawan) { // Edit
            $stmt = $conn->prepare("UPDATE KARYAWAN SET Nama_Karyawan=?, Jenis_Kelamin=?, Telepon=?, Alamat=?, Tgl_Lahir=?, Tgl_Awal_Kerja=?, Id_Pengguna=?, Id_Jabatan=?, Status=? WHERE Id_Karyawan=?");
            $stmt->bind_param("ssssssssss", $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $id_pengguna, $id_jabatan, $status, $id_karyawan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $prefix = "KR";
            $result = $conn->query("SELECT Id_Karyawan FROM KARYAWAN WHERE Id_Karyawan LIKE 'KR%' ORDER BY Id_Karyawan DESC LIMIT 1");
            $last_id_num = ($row = $result->fetch_assoc()) ? intval(substr($row['Id_Karyawan'], 2)) : 0;
            $id_karyawan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO KARYAWAN (Id_Karyawan, Nama_Karyawan, Jenis_Kelamin, Telepon, Alamat, Tgl_Lahir, Tgl_Awal_Kerja, Id_Pengguna, Id_Jabatan, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $id_karyawan_new, $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $id_pengguna, $id_jabatan, $status);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data karyawan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data karyawan: " . $stmt->error);
        
        $stmt->close();
        header('Location: karyawan.php?action=list');
        exit;
    }
}

$karyawan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Karyawan';
    $stmt = $conn->prepare("SELECT * FROM KARYAWAN WHERE Id_Karyawan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $karyawan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$karyawan_data) {
        set_flash_message('error', 'Data karyawan tidak ditemukan.');
        header('Location: karyawan.php?action=list');
        exit;
    }
    
    // Ambil data pengguna yang terpilih untuk mode edit agar tetap muncul di dropdown
    $stmt_pengguna_edit = $conn->prepare("SELECT Id_Pengguna, Email FROM PENGGUNA WHERE Id_Pengguna = ?");
    $stmt_pengguna_edit->bind_param("s", $karyawan_data['Id_Pengguna']);
    $stmt_pengguna_edit->execute();
    $pengguna_terpilih = $stmt_pengguna_edit->get_result()->fetch_assoc();
    if ($pengguna_terpilih && !in_array($pengguna_terpilih, $pengguna_list)) {
        array_unshift($pengguna_list, $pengguna_terpilih);
    }
    $stmt_pengguna_edit->close();

} elseif ($action === 'add') {
    $page_title = 'Tambah Karyawan';
}

generate_csrf_token();

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Style custom untuk input tanggal modern */
.date-input-container {
    position: relative;
}
input[type="date"] {
    padding-right: 2.5rem; /* Ruang untuk ikon */
}
input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 0;
    top: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    margin: 0;
    color: transparent;
    background: transparent;
    cursor: pointer;
}
.date-input-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #9ca3af; /* gray-400 */
}
</style>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Karyawan</h2>
                <p class="text-gray-500 text-sm">Kelola data, status, dan informasi kepegawaian.</p>
            </div>
            <a href="karyawan.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Karyawan
            </a>
        </div>
        
        <form method="get" action="karyawan.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau ID karyawan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Status</option>
                    <option value="Aktif" <?= $status_filter == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Nonaktif" <?= $status_filter == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama Karyawan</th>
                        <th class="px-6 py-3">Jabatan</th>
                        <th class="px-6 py-3">Telepon</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build query dinamis untuk count
                    $count_params = [];
                    $types_string_count = '';
                    $count_sql = "SELECT COUNT(k.Id_Karyawan) as total FROM KARYAWAN k WHERE (k.Nama_Karyawan LIKE ? OR k.Id_Karyawan LIKE ?)";
                    $search_param = "%" . $search . "%";
                    array_push($count_params, $search_param, $search_param);
                    $types_string_count .= 'ss';

                    if ($status_filter) {
                        $count_sql .= " AND k.Status = ?";
                        array_push($count_params, $status_filter);
                        $types_string_count .= 's';
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    $stmt_count->bind_param($types_string_count, ...$count_params);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    // Build query dinamis untuk data
                    $data_params = $count_params; // Copy params from count query
                    $types_string_data = $types_string_count;
                    $sql = "SELECT k.*, j.Nama_Jabatan FROM KARYAWAN k LEFT JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan WHERE (k.Nama_Karyawan LIKE ? OR k.Id_Karyawan LIKE ?)";
                    if ($status_filter) {
                        $sql .= " AND k.Status = ?";
                    }
                    $sql .= " ORDER BY k.Nama_Karyawan ASC LIMIT ? OFFSET ?";
                    array_push($data_params, $records_per_page, $offset);
                    $types_string_data .= 'ii';
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types_string_data, ...$data_params);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Karyawan']) ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                            <td class="px-6 py-4"><?= e($row['Nama_Jabatan']) ?? 'N/A' ?></td>
                            <td class="px-6 py-4"><?= e($row['Telepon']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $row['Status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= e($row['Status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="karyawan.php?action=edit&id=<?= e($row['Id_Karyawan']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="karyawan.php?action=delete&id=<?= e($row['Id_Karyawan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile;
                    } else {
                         echo '<tr><td colspan="6" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    $stmt->close(); $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        echo generate_pagination_links($page, $total_pages, 'karyawan.php', ['action' => 'list', 'search' => $search, 'status' => $status_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Data Karyawan</h2>
        <p class="text-center text-gray-500 mb-8">Lengkapi semua informasi yang diperlukan.</p>
        
        <form method="POST" action="karyawan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="Id_Karyawan" value="<?= e($karyawan_data['Id_Karyawan'] ?? '') ?>">
            
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <label for="Nama_Karyawan" class="block mb-2 text-sm font-medium text-gray-700">Nama Karyawan</label>
                    <input type="text" id="Nama_Karyawan" name="Nama_Karyawan" value="<?= e($karyawan_data['Nama_Karyawan'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="Id_Jabatan" class="block mb-2 text-sm font-medium text-gray-700">Jabatan</label>
                    <select id="Id_Jabatan" name="Id_Jabatan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">- Pilih Jabatan -</option>
                        <?php foreach($jabatan_list as $jabatan): ?>
                        <option value="<?= e($jabatan['Id_Jabatan']) ?>" <?= (isset($karyawan_data) && $karyawan_data['Id_Jabatan'] == $jabatan['Id_Jabatan']) ? 'selected' : '' ?>><?= e($jabatan['Nama_Jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="Jenis_Kelamin" class="block mb-2 text-sm font-medium text-gray-700">Jenis Kelamin</label>
                    <select id="Jenis_Kelamin" name="Jenis_Kelamin" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="Laki-laki" <?= (isset($karyawan_data) && $karyawan_data['Jenis_Kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= (isset($karyawan_data) && $karyawan_data['Jenis_Kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="Telepon" class="block mb-2 text-sm font-medium text-gray-700">No. Telepon</label>
                    <input type="tel" id="Telepon" name="Telepon" value="<?= e($karyawan_data['Telepon'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div class="md:col-span-2">
                    <label for="Alamat" class="block mb-2 text-sm font-medium text-gray-700">Alamat</label>
                    <textarea id="Alamat" name="Alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required><?= e($karyawan_data['Alamat'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="Tgl_Lahir" class="block mb-2 text-sm font-medium text-gray-700">Tanggal Lahir</label>
                    <div class="date-input-container">
                        <input type="date" id="Tgl_Lahir" name="Tgl_Lahir" min="1960-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($karyawan_data['Tgl_Lahir'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <div class="date-input-icon">
                           <i class="fa-solid fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="Tgl_Awal_Kerja" class="block mb-2 text-sm font-medium text-gray-700">Tanggal Awal Kerja</label>
                    <div class="date-input-container">
                        <input type="date" id="Tgl_Awal_Kerja" name="Tgl_Awal_Kerja" min="1960-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($karyawan_data['Tgl_Awal_Kerja'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                         <div class="date-input-icon">
                           <i class="fa-solid fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="Id_Pengguna" class="block mb-2 text-sm font-medium text-gray-700">Akun Pengguna (Email)</label>
                    <select id="Id_Pengguna" name="Id_Pengguna" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">- Pilih Akun Pengguna -</option>
                         <?php foreach($pengguna_list as $pengguna): ?>
                        <option value="<?= e($pengguna['Id_Pengguna']) ?>" <?= (isset($karyawan_data) && $karyawan_data['Id_Pengguna'] == $pengguna['Id_Pengguna']) ? 'selected' : '' ?>><?= e($pengguna['Email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hanya menampilkan akun 'Karyawan' yang belum terikat.</p>
                </div>
                <div>
                    <label for="Status" class="block mb-2 text-sm font-medium text-gray-700">Status Karyawan</label>
                    <select id="Status" name="Status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="Aktif" <?= (!isset($karyawan_data) || (isset($karyawan_data) && $karyawan_data['Status'] == 'Aktif')) ? 'selected' : '' ?>>Aktif</option>
                        <option value="Nonaktif" <?= (isset($karyawan_data) && $karyawan_data['Status'] == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="karyawan.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
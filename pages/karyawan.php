<?php
// 1. SETUP & LOGIKA
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Karyawan';

// Ambil data untuk dropdown forms
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan ORDER BY nama_jabatan")->fetch_all(MYSQLI_ASSOC);
// Ambil akun pengguna 'karyawan' yang belum terikat
$query_pengguna = "
    SELECT p.id_pengguna, p.email 
    FROM pengguna p
    LEFT JOIN karyawan k ON p.id_pengguna = k.id_pengguna
    WHERE p.level = 'karyawan' AND k.id_karyawan IS NULL";
$pengguna_list = $conn->query($query_pengguna)->fetch_all(MYSQLI_ASSOC);


// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM karyawan WHERE id_karyawan = ?");
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

    $id_karyawan = $_POST['id_karyawan'] ?? null;
    $nama = trim($_POST['nama_karyawan']);
    $jk = $_POST['jenis_kelamin'];
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $tgl_lahir = $_POST['tgl_lahir'];
    $tgl_masuk = $_POST['tgl_awal_kerja'];
    $id_pengguna = $_POST['id_pengguna'];
    $id_jabatan = $_POST['id_jabatan'];
    $status = $_POST['status'];

    if (empty($nama) || empty($jk) || empty($telepon) || empty($alamat) || empty($tgl_lahir) || empty($tgl_masuk) || empty($id_pengguna) || empty($id_jabatan) || empty($status)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_karyawan) { // Edit
            $stmt = $conn->prepare("UPDATE karyawan SET nama_karyawan=?, jenis_kelamin=?, telepon=?, alamat=?, tgl_lahir=?, tgl_awal_kerja=?, id_pengguna=?, id_jabatan=?, status=? WHERE id_karyawan=?");
            $stmt->bind_param("ssssssssss", $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $id_pengguna, $id_jabatan, $status, $id_karyawan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $prefix = "KR";
            $result = $conn->query("SELECT id_karyawan FROM karyawan WHERE id_karyawan LIKE 'KR%' ORDER BY id_karyawan DESC LIMIT 1");
            $last_id_num = ($row = $result->fetch_assoc()) ? intval(substr($row['id_karyawan'], 2)) : 0;
            $id_karyawan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO karyawan (id_karyawan, nama_karyawan, jenis_kelamin, telepon, alamat, tgl_lahir, tgl_awal_kerja, id_pengguna, id_jabatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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

// --- PERSIAPAN DATA UNTUK FORM EDIT ---
$karyawan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Karyawan';
    $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id_karyawan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $karyawan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$karyawan_data) {
        set_flash_message('error', 'Data karyawan tidak ditemukan.');
        header('Location: karyawan.php?action=list');
        exit;
    }
    // Tambahkan pengguna yang sedang diedit ke daftar dropdown agar bisa dipilih kembali
    $stmt_pengguna_edit = $conn->prepare("SELECT id_pengguna, email FROM pengguna WHERE id_pengguna = ?");
    $stmt_pengguna_edit->bind_param("s", $karyawan_data['id_pengguna']);
    $stmt_pengguna_edit->execute();
    $pengguna_terpilih = $stmt_pengguna_edit->get_result()->fetch_assoc();
    if ($pengguna_terpilih) {
        array_unshift($pengguna_list, $pengguna_terpilih);
    }
    $stmt_pengguna_edit->close();

} elseif ($action === 'add') {
    $page_title = 'Tambah Karyawan';
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
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Karyawan</h2>
            <a href="karyawan.php?action=add" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Karyawan
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Telepon</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = db_connect();
                    $result = $conn->query("SELECT k.*, j.nama_jabatan FROM karyawan k LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan ORDER BY k.nama_karyawan ASC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($row['id_karyawan']) ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['nama_karyawan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['nama_jabatan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['telepon']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $row['status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= e($row['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="karyawan.php?action=edit&id=<?= e($row['id_karyawan']) ?>" class="bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-blue-600">Edit</a>
                            <a href="karyawan.php?action=delete&id=<?= e($row['id_karyawan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; $conn->close(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?= e(ucfirst($action)) ?> Data Karyawan</h2>
        <form method="POST" action="karyawan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_karyawan" value="<?= e($karyawan_data['id_karyawan'] ?? '') ?>">
            
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <label for="nama_karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                    <input type="text" id="nama_karyawan" name="nama_karyawan" value="<?= e($karyawan_data['nama_karyawan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="id_jabatan" class="block mb-2 text-sm font-bold text-gray-700">Jabatan</label>
                    <select id="id_jabatan" name="id_jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">- Pilih Jabatan -</option>
                        <?php foreach($jabatan_list as $jabatan): ?>
                        <option value="<?= e($jabatan['id_jabatan']) ?>" <?= (isset($karyawan_data) && $karyawan_data['id_jabatan'] == $jabatan['id_jabatan']) ? 'selected' : '' ?>><?= e($jabatan['nama_jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="jenis_kelamin" class="block mb-2 text-sm font-bold text-gray-700">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="Laki-laki" <?= (isset($karyawan_data) && $karyawan_data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= (isset($karyawan_data) && $karyawan_data['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="telepon" class="block mb-2 text-sm font-bold text-gray-700">No. Telepon</label>
                    <input type="tel" id="telepon" name="telepon" value="<?= e($karyawan_data['telepon'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                 <div class="md:col-span-2">
                    <label for="alamat" class="block mb-2 text-sm font-bold text-gray-700">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" required><?= e($karyawan_data['alamat'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="tgl_lahir" class="block mb-2 text-sm font-bold text-gray-700">Tanggal Lahir</label>
                    <input type="date" id="tgl_lahir" name="tgl_lahir" value="<?= e($karyawan_data['tgl_lahir'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label for="tgl_awal_kerja" class="block mb-2 text-sm font-bold text-gray-700">Tanggal Awal Kerja</label>
                    <input type="date" id="tgl_awal_kerja" name="tgl_awal_kerja" value="<?= e($karyawan_data['tgl_awal_kerja'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label for="id_pengguna" class="block mb-2 text-sm font-bold text-gray-700">Akun Pengguna (Email)</label>
                    <select id="id_pengguna" name="id_pengguna" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">- Pilih Akun Pengguna -</option>
                         <?php foreach($pengguna_list as $pengguna): ?>
                        <option value="<?= e($pengguna['id_pengguna']) ?>" <?= (isset($karyawan_data) && $karyawan_data['id_pengguna'] == $pengguna['id_pengguna']) ? 'selected' : '' ?>><?= e($pengguna['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hanya menampilkan akun 'karyawan' yang belum terikat.</p>
                </div>
                <div>
                    <label for="status" class="block mb-2 text-sm font-bold text-gray-700">Status Karyawan</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="Aktif" <?= (!isset($karyawan_data) || (isset($karyawan_data) && $karyawan_data['status'] == 'Aktif')) ? 'selected' : '' ?>>Aktif</option>
                        <option value="Nonaktif" <?= (isset($karyawan_data) && $karyawan_data['status'] == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="karyawan.php?action=list" class="bg-gray-500 text-white px-4 py-2.5 rounded-md hover:bg-gray-600 font-semibold text-sm">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2.5 rounded-md hover:bg-green-700 font-semibold text-sm">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
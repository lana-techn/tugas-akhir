<?php
// 1. SETUP & ROUTING
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list'; // Aksi default: menampilkan daftar
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Karyawan';

// Ambil data untuk dropdown forms
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan")->fetch_all(MYSQLI_ASSOC);
$pengguna_list = $conn->query("SELECT id_pengguna, email FROM pengguna WHERE level = 'karyawan'")->fetch_all(MYSQLI_ASSOC);

// 2. LOGIC HANDLING
// =======================================

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM karyawan WHERE id_karyawan = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data karyawan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data karyawan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: karyawan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Validasi CSRF gagal.');
    }

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

    // Validasi Sederhana
    if (empty($nama) || empty($jk) || empty($telepon) || empty($alamat) || empty($tgl_lahir) || empty($tgl_masuk) || empty($id_pengguna) || empty($id_jabatan) || empty($status)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_karyawan) { // --- Proses EDIT ---
            $stmt = $conn->prepare("UPDATE karyawan SET nama_karyawan=?, jenis_kelamin=?, telepon=?, alamat=?, tgl_lahir=?, tgl_awal_kerja=?, id_pengguna=?, id_jabatan=?, status=? WHERE id_karyawan=?");
            $stmt->bind_param("ssssssssss", $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $id_pengguna, $id_jabatan, $status, $id_karyawan);
            $action_text = 'diperbarui';
        } else { // --- Proses TAMBAH ---
            $prefix = "KR";
            $result = $conn->query("SELECT id_karyawan FROM karyawan WHERE id_karyawan LIKE 'KR%' ORDER BY id_karyawan DESC LIMIT 1");
            $last_id_num = $result->num_rows > 0 ? intval(substr($result->fetch_assoc()['id_karyawan'], 2)) : 0;
            $id_karyawan_new = $prefix . str_pad($last_id_num + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO karyawan (id_karyawan, nama_karyawan, jenis_kelamin, telepon, alamat, tgl_lahir, tgl_awal_kerja, id_pengguna, id_jabatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $id_karyawan_new, $nama, $jk, $telepon, $alamat, $tgl_lahir, $tgl_masuk, $id_pengguna, $id_jabatan, $status);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data karyawan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data karyawan: " . $stmt->error);
        }
        $stmt->close();
        header('Location: karyawan.php?action=list');
        exit;
    }
}

// 3. DATA PREPARATION FOR VIEW
// =======================================
$karyawan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Karyawan';
    $stmt = $conn->prepare("SELECT * FROM karyawan WHERE id_karyawan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $karyawan_data = $result->fetch_assoc();
    $stmt->close();
    if (!$karyawan_data) {
        set_flash_message('error', 'Data karyawan tidak ditemukan.');
        header('Location: karyawan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Karyawan';
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
            <a href="karyawan.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white bg-[#388e3c] text-white"><i class="fas fa-users w-6 text-center"></i> <span class="ml-2">Karyawan</span></a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white mt-4"><i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span></a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        <?php display_flash_message(); ?>

        <?php if ($action === 'list'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR KARYAWAN</h2>
                    <a href="karyawan.php?action=add" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah Karyawan</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
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
                            $query = "SELECT k.*, j.nama_jabatan FROM karyawan k LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan ORDER BY k.nama_karyawan ASC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs"><?= e($row['id_karyawan']) ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['nama_karyawan']) ?></td>
                                <td class="px-4 py-3"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3"><?= e($row['telepon']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $row['status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="karyawan.php?action=edit&id=<?= e($row['id_karyawan']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <a href="karyawan.php?action=delete&id=<?= e($row['id_karyawan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action)) ?> Karyawan</h2>
                <form method="POST" action="karyawan.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_karyawan" value="<?= e($karyawan_data['id_karyawan'] ?? '') ?>">
                    <div class="form-grid">
                        <div>
                            <div class="mb-4">
                                <label for="nama_karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                                <input type="text" id="nama_karyawan" name="nama_karyawan" value="<?= e($karyawan_data['nama_karyawan'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            </div>
                             <div class="mb-4">
                                <label for="jenis_kelamin" class="block mb-2 text-sm font-bold text-gray-700">Jenis Kelamin</label>
                                <select id="jenis_kelamin" name="jenis_kelamin" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                    <option value="Laki-laki" <?= (isset($karyawan_data) && $karyawan_data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan" <?= (isset($karyawan_data) && $karyawan_data['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="telepon" class="block mb-2 text-sm font-bold text-gray-700">No. Telepon</label>
                                <input type="text" id="telepon" name="telepon" value="<?= e($karyawan_data['telepon'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            </div>
                             <div class="mb-4">
                                <label for="alamat" class="block mb-2 text-sm font-bold text-gray-700">Alamat</label>
                                <textarea id="alamat" name="alamat" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md" required><?= e($karyawan_data['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div>
                            <div class="mb-4">
                                <label for="tgl_lahir" class="block mb-2 text-sm font-bold text-gray-700">Tanggal Lahir</label>
                                <input type="date" id="tgl_lahir" name="tgl_lahir" value="<?= e($karyawan_data['tgl_lahir'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="tgl_awal_kerja" class="block mb-2 text-sm font-bold text-gray-700">Tanggal Awal Kerja</label>
                                <input type="date" id="tgl_awal_kerja" name="tgl_awal_kerja" value="<?= e($karyawan_data['tgl_awal_kerja'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="id_jabatan" class="block mb-2 text-sm font-bold text-gray-700">Jabatan</label>
                                <select id="id_jabatan" name="id_jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                    <option value="">- Pilih Jabatan -</option>
                                    <?php foreach($jabatan_list as $jabatan): ?>
                                    <option value="<?= e($jabatan['id_jabatan']) ?>" <?= (isset($karyawan_data) && $karyawan_data['id_jabatan'] == $jabatan['id_jabatan']) ? 'selected' : '' ?>><?= e($jabatan['nama_jabatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="mb-4">
                                <label for="id_pengguna" class="block mb-2 text-sm font-bold text-gray-700">Akun Pengguna</label>
                                <select id="id_pengguna" name="id_pengguna" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                    <option value="">- Pilih Akun Pengguna -</option>
                                     <?php foreach($pengguna_list as $pengguna): ?>
                                    <option value="<?= e($pengguna['id_pengguna']) ?>" <?= (isset($karyawan_data) && $karyawan_data['id_pengguna'] == $pengguna['id_pengguna']) ? 'selected' : '' ?>><?= e($pengguna['email']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="status" class="block mb-2 text-sm font-bold text-gray-700">Status</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                    <option value="Aktif" <?= (isset($karyawan_data) && $karyawan_data['status'] == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= (isset($karyawan_data) && $karyawan_data['status'] == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-4 mt-6">
                        <a href="karyawan.php?action=list" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
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
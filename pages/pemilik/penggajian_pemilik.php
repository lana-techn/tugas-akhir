<?php
$page_title = 'Persetujuan Gaji';
$current_page = 'penggajian_pemilik';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('pemilik');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id_gaji = $_GET['id'] ?? null;

// --- PROSES AKSI (SETUJUI, TOLAK, HAPUS) ---
if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    
    // Proses Setujui atau Tolak
    if (in_array($action, ['approve', 'reject']) && $id_gaji) {
        $new_status = ($action === 'approve') ? 'Disetujui' : 'Ditolak';
        $message = ($action === 'approve') ? 'disetujui' : 'ditolak';

        $stmt = $conn->prepare("UPDATE GAJI SET Status = ? WHERE Id_Gaji = ? AND Status = 'Diajukan'");
        $stmt->bind_param("ss", $new_status, $id_gaji);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            set_flash_message('success', "Penggajian berhasil {$message}.");
        } else {
            set_flash_message('error', "Gagal memproses penggajian. Mungkin sudah diproses sebelumnya.");
        }
        $stmt->close();
    }

    if ($action === 'delete_approved' && $id_gaji) {
        $conn->begin_transaction();
        try {
            $stmt_detail = $conn->prepare("DELETE FROM DETAIL_GAJI WHERE Id_Gaji = ?");
            $stmt_detail->bind_param("s", $id_gaji);
            $stmt_detail->execute();
            $stmt_detail->close();
            $stmt_gaji = $conn->prepare("DELETE FROM GAJI WHERE Id_Gaji = ? AND Status = 'Disetujui'");
            $stmt_gaji->bind_param("s", $id_gaji);
            $stmt_gaji->execute();
            if ($stmt_gaji->affected_rows > 0) {
                set_flash_message('success', 'Data gaji yang disetujui berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data.');
            }
            $stmt_gaji->close();
            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            set_flash_message('error', 'Terjadi kesalahan database saat menghapus.');
        }
    }

    header('Location: penggajian_pemilik.php');
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil data untuk filter
$karyawan_list = $conn->query("SELECT Id_Karyawan, Nama_Karyawan FROM KARYAWAN ORDER BY Nama_Karyawan ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

generate_csrf_token();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Persetujuan Gaji Karyawan</h2>
            <p class="text-gray-500 text-sm">Tinjau dan proses pengajuan gaji yang masuk dari admin.</p>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end">
        <input type="hidden" name="action" value="list">
        <div>
            <label for="filter_karyawan" class="text-sm font-medium text-gray-600">Nama Karyawan</label>
            <select name="karyawan" id="filter_karyawan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Karyawan</option>
                <?php foreach($karyawan_list as $k): ?>
                <option value="<?= e($k['Id_Karyawan']) ?>" <?= ($_GET['karyawan'] ?? '') == $k['Id_Karyawan'] ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_bulan" class="text-sm font-medium text-gray-600">Bulan</label>
            <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Bulan</option>
                <?php foreach($bulan_list as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($_GET['bulan'] ?? '') == $num ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_tahun" class="text-sm font-medium text-gray-600">Tahun</label>
            <input type="number" name="tahun" id="filter_tahun" value="<?= e($_GET['tahun'] ?? date('Y')) ?>" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">Tampilkan</button>
            <a href="penggajian_pemilik.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-6 py-3">ID Gaji</th>
                    <th class="px-6 py-3">Nama Karyawan</th>
                    <th class="px-6 py-3">Jabatan</th>
                    <th class="px-6 py-3">Periode Gaji</th>
                    <th class="px-6 py-3 text-left">Gaji Bersih</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query count untuk pagination
                $count_sql = "SELECT COUNT(g.Id_Gaji) as total 
                              FROM GAJI g 
                              JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
                              JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan 
                              WHERE (g.Status = 'Diajukan' OR g.Status = 'Disetujui' OR g.Status = 'Ditolak')";
                $count_params = [];
                $count_types = '';
                
                if (!empty($_GET['karyawan'])) { 
                    $count_sql .= " AND g.Id_Karyawan = ?"; 
                    $count_params[] = $_GET['karyawan']; 
                    $count_types .= 's'; 
                }
                if (!empty($_GET['bulan'])) { 
                    $count_sql .= " AND MONTH(g.Tgl_Gaji) = ?"; 
                    $count_params[] = $_GET['bulan']; 
                    $count_types .= 'i'; 
                }
                if (!empty($_GET['tahun'])) { 
                    $count_sql .= " AND YEAR(g.Tgl_Gaji) = ?"; 
                    $count_params[] = $_GET['tahun']; 
                    $count_types .= 'i'; 
                }
                
                $stmt_count = $conn->prepare($count_sql);
                if (!empty($count_types)) { 
                    $stmt_count->bind_param($count_types, ...$count_params); 
                }
                $stmt_count->execute();
                $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total_records / $records_per_page);
                $stmt_count->close();

                // Query dinamis berdasarkan filter
                $sql = "SELECT g.Id_Gaji, k.Nama_Karyawan, j.Nama_Jabatan, g.Tgl_Gaji, g.Gaji_Bersih, g.Status 
                        FROM GAJI g 
                        JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
                        JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan 
                        WHERE (g.Status = 'Diajukan' OR g.Status = 'Disetujui' OR g.Status = 'Ditolak')";
                $params = [];
                $types = '';
                
                if (!empty($_GET['karyawan'])) { 
                    $sql .= " AND g.Id_Karyawan = ?"; 
                    $params[] = $_GET['karyawan']; 
                    $types .= 's'; 
                }
                if (!empty($_GET['bulan'])) { 
                    $sql .= " AND MONTH(g.Tgl_Gaji) = ?"; 
                    $params[] = $_GET['bulan']; 
                    $types .= 'i'; 
                }
                if (!empty($_GET['tahun'])) { 
                    $sql .= " AND YEAR(g.Tgl_Gaji) = ?"; 
                    $params[] = $_GET['tahun']; 
                    $types .= 'i'; 
                }
                
                $sql .= " ORDER BY FIELD(g.Status, 'Diajukan', 'Disetujui', 'Ditolak'), g.Tgl_Gaji DESC LIMIT ? OFFSET ?";
                $params[] = $records_per_page;
                $params[] = $offset;
                $types .= 'ii';
                
                $stmt = $conn->prepare($sql);
                if (!empty($types)) { 
                    $stmt->bind_param($types, ...$params); 
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        // Logika untuk warna status
                        $status_class = '';
                        if ($row['Status'] == 'Diajukan') $status_class = 'bg-yellow-100 text-yellow-800';
                        if ($row['Status'] == 'Disetujui') $status_class = 'bg-blue-100 text-blue-800';
                        if ($row['Status'] == 'Ditolak') $status_class = 'bg-red-100 text-red-800';
                ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                    <td class="px-6 py-4"><?= e($row['Nama_Jabatan']) ?></td>
                    <td class="px-6 py-4"><?= e(date('F Y', strtotime($row['Tgl_Gaji']))) ?></td>
                    <td class="px-6 py-4 text-left font-semibold text-green-700">Rp <?= number_format($row['Gaji_Bersih'], 2, ',', '.') ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                            <?= e($row['Status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="detail_gaji_pemilik.php?id=<?= e($row['Id_Gaji']) ?>" class="text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">Detail</a>
                            <?php if ($row['Status'] == 'Diajukan'): ?>
                                <a href="penggajian_pemilik.php?action=approve&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-green-500 px-3 py-1 rounded-md hover:bg-green-600" onclick="return confirm('Apakah Anda yakin ingin menyetujui penggajian ini?')">Setujui</a>
                                <a href="penggajian_pemilik.php?action=reject&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600" onclick="return confirm('Apakah Anda yakin ingin menolak penggajian ini?')">Tolak</a>
                            <?php elseif ($row['Status'] == 'Disetujui'): ?>
                                <a href="penggajian_pemilik.php?action=delete_approved&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data yang sudah disetujui ini?')">Hapus</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">
                            <i class="fa-solid fa-folder-open text-2xl text-gray-400 mb-2"></i>
                            <p>Tidak ada data gaji yang perlu diproses saat ini.</p>
                        </td>
                    </tr>
                <?php 
                endif; 
                $stmt->close();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <?php 
    // Generate pagination links with filter parameters
    $pagination_params = [];
    if (!empty($_GET['karyawan'])) $pagination_params['karyawan'] = $_GET['karyawan'];
    if (!empty($_GET['bulan'])) $pagination_params['bulan'] = $_GET['bulan'];
    if (!empty($_GET['tahun'])) $pagination_params['tahun'] = $_GET['tahun'];
    
    echo generate_pagination_links($page, $total_pages, 'penggajian_pemilik.php', $pagination_params);
    ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
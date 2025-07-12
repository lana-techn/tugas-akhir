<?php
$page_title = 'Persetujuan Gaji';
$current_page = 'penggajian_pemilik';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('pemilik');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id_gaji = $_GET['id'] ?? null;

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES PERSETUJUAN ---
if ($action === 'approve' && $id_gaji) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("UPDATE GAJI SET Status = 'Disetujui' WHERE Id_Gaji = ?");
        $stmt->bind_param("s", $id_gaji);
        if ($stmt->execute()) {
            set_flash_message('success', 'Penggajian berhasil disetujui.');
        } else {
            set_flash_message('error', 'Gagal menyetujui penggajian.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: penggajian_pemilik.php');
    exit;
}

generate_csrf_token();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Persetujuan Gaji Karyawan</h2>
            <p class="text-gray-500 text-sm">Tinjau dan setujui pengajuan gaji yang masuk.</p>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <form method="get" action="penggajian_pemilik.php" class="mb-6">
        <div class="relative">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari berdasarkan nama karyawan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-search text-gray-400"></i>
            </div>
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
                    <th class="px-6 py-3 text-right">Gaji Bersih</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk count
                $count_sql = "SELECT COUNT(g.Id_Gaji) as total 
                              FROM GAJI g
                              JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
                              WHERE g.Status = 'Diajukan' AND k.Nama_Karyawan LIKE ?";
                $stmt_count = $conn->prepare($count_sql);
                $search_param = "%" . $search . "%";
                $stmt_count->bind_param("s", $search_param);
                $stmt_count->execute();
                $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total_records / $records_per_page);
                $stmt_count->close();

                // Query untuk data
                $sql = "SELECT g.Id_Gaji, k.Nama_Karyawan, j.Nama_Jabatan, g.Tgl_Gaji, g.Gaji_Bersih
                        FROM GAJI g
                        JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
                        JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
                        WHERE g.Status = 'Diajukan' AND k.Nama_Karyawan LIKE ?
                        ORDER BY g.Tgl_Gaji DESC
                        LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                    <td class="px-6 py-4"><?= e($row['Nama_Jabatan']) ?></td>
                    <td class="px-6 py-4"><?= e(date('F Y', strtotime($row['Tgl_Gaji']))) ?></td>
                    <td class="px-6 py-4 text-right font-semibold text-green-700">Rp <?= number_format($row['Gaji_Bersih'], 0, ',', '.') ?></td>
                    <td class="px-6 py-4 text-center">
                        <a href="penggajian_pemilik.php?action=approve&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" 
                           class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1.5 rounded-full hover:bg-green-200 transition-colors"
                           onclick="return confirm('Apakah Anda yakin ingin menyetujui penggajian ini?')">
                           <i class="fa-solid fa-check mr-1"></i> Setujui
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            <i class="fa-solid fa-check-double text-2xl text-green-400 mb-2"></i>
                            <p>Tidak ada pengajuan gaji baru yang perlu disetujui saat ini.</p>
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
    // Menampilkan pagination
    echo generate_pagination_links($page, $total_pages, 'penggajian_pemilik.php', ['search' => $search]);
    ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
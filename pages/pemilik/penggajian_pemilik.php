<?php
$page_title = 'Persetujuan Gaji';
$current_page = 'penggajian_pemilik';
require_once __DIR__ . '/../../includes/functions.php';
require_login('pemilik');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id_gaji = $_GET['id'] ?? null;

// --- PROSES PERSETUJUAN ---
if ($action === 'approve' && $id_gaji) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Update status gaji menjadi 'Disetujui'
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
?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="flex-1 p-8">
    <h1 class="text-3xl font-bold text-[#2e7d32] mb-6">Pengajuan Gaji Karyawan</h1>
    
    <?php display_flash_message(); ?>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Menunggu Persetujuan</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">ID Gaji</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Periode Gaji</th>
                        <th class="px-4 py-3 text-right">Gaji Bersih</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil data gaji yang statusnya masih 'Diajukan'
                    $query = "
                        SELECT g.Id_Gaji, k.Nama_Karyawan, j.Nama_Jabatan, g.Tgl_Gaji, g.Gaji_Bersih
                        FROM GAJI g
                        JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
                        JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
                        WHERE g.Status = 'Diajukan'
                        ORDER BY g.Tgl_Gaji DESC
                    ";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['Nama_Jabatan']) ?></td>
                        <td class="px-4 py-3"><?= e(date('F Y', strtotime($row['Tgl_Gaji']))) ?></td>
                        <td class="px-4 py-3 text-right font-semibold">Rp <?= number_format($row['Gaji_Bersih'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-center">
                            <a href="penggajian_pemilik.php?action=approve&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" 
                               class="bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-md hover:bg-green-700"
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
                            <td colspan="6" class="text-center py-5 text-gray-500">Tidak ada pengajuan gaji yang perlu disetujui saat ini.</td>
                        </tr>
                    <?php 
                    endif; 
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
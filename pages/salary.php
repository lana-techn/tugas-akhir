<?php
// 1. SETUP & ROUTING (Tidak ada perubahan di sini)
// =======================================
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list_gapok';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Gaji';
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan")->fetch_all(MYSQLI_ASSOC);

// ... (Semua logika PHP lainnya tetap sama seperti sebelumnya) ...

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
        /* Menambahkan gaya notifikasi dari CSS asli Anda */
        .notif {
            padding: 12px 15px;
            background: white;
            border-radius: 5px;
            font-size: 15px;
            color: #444;
            border-left-width: 5px;
            margin-bottom: 20px;
        }
        .notif-success {
            border-left-color: #43a047;
        }
        .notif-error {
            border-left-color: #e53935;
        }
    </style>
</head>
<body class="bg-[#e8f5e9] font-['Segoe_UI',_sans-serif]">
<div class="flex min-h-screen">
    <div class="w-64 flex-shrink-0 bg-gradient-to-b from-[#b2f2bb] to-white text-black">
        <div class="p-5 bg-[#98eba3] text-center">
            <h3 class="text-xl font-bold">ADMIN</h3>
        </div>
        <nav class="mt-4">
            <a href="../index.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200">
                <i class="fas fa-home w-6 text-center"></i> <span class="ml-2">Dashboard</span>
            </a>
            <a href="salary.php?action=list_gapok" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 <?= (in_array($action, ['list_gapok', 'add_gapok', 'edit_gapok'])) ? 'bg-[#388e3c] text-white' : '' ?>">
                <i class="fas fa-money-check-dollar w-6 text-center"></i> <span class="ml-2">Gaji Pokok</span>
            </a>
            <a href="salary.php?action=new_payroll" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 <?= (in_array($action, ['new_payroll', 'detail_payroll'])) ? 'bg-[#388e3c] text-white' : '' ?>">
                <i class="fas fa-file-invoice-dollar w-6 text-center"></i> <span class="ml-2">Pengajuan Gaji</span>
            </a>
            <a href="../auth/logout.php" class="flex items-center px-5 py-3 text-sm text-black hover:bg-[#388e3c] hover:text-white transition-colors duration-200 mt-4">
                <i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-2">Logout</span>
            </a>
        </nav>
    </div>

    <div class="flex-1 p-8">
        <div class="text-right text-sm text-gray-600 mb-4"><?= e($_SESSION['email'] ?? '') ?></div>
        
        <?php display_flash_message(); ?>

        <?php // ===== KONTEN DINAMIS BERDASARKAN AKSI ===== ?>

        <?php if ($action === 'list_gapok'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#2e7d32]">DAFTAR GAJI POKOK</h2>
                    <a href="salary.php?action=add_gapok" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] text-sm font-bold">Tambah</a>
                </div>
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-sm text-center text-gray-700">
                        <thead class="text-xs uppercase bg-[#a5d6a7]">
                            <tr>
                                <th scope="col" class="px-4 py-3 border border-gray-300">No</th>
                                <th scope="col" class="px-4 py-3 border border-gray-300">Nama Jabatan</th>
                                <th scope="col" class="px-4 py-3 border border-gray-300">Masa Kerja (Thn)</th>
                                <th scope="col" class="px-4 py-3 border border-gray-300">Nominal</th>
                                <th scope="col" class="px-4 py-3 border border-gray-300">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $result = $conn->query("SELECT gp.*, j.nama_jabatan FROM gaji_pokok gp JOIN jabatan j ON gp.id_jabatan = j.id_jabatan ORDER BY j.nama_jabatan, gp.masa_kerja");
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                            <tr class="bg-white border-b border-gray-300">
                                <td class="px-4 py-3 border border-gray-300"><?= $no++ ?></td>
                                <td class="px-4 py-3 border border-gray-300 text-left"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3 border border-gray-300"><?= e($row['masa_kerja']) ?></td>
                                <td class="px-4 py-3 border border-gray-300">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 border border-gray-300">
                                    <a href="salary.php?action=edit_gapok&id=<?= e($row['id_gapok']) ?>" class="bg-[#0288d1] text-white text-xs px-3 py-1 rounded hover:bg-[#0277bd]">Edit</a>
                                    <a href="salary.php?action=delete_gapok&id=<?= e($row['id_gapok']) ?>" class="bg-[#e53935] text-white text-xs px-3 py-1 rounded hover:bg-[#c62828]" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add_gapok' || $action === 'edit_gapok'): ?>
            <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
                 <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6"><?= e(ucfirst($action) === 'Add_gapok' ? 'TAMBAH' : 'EDIT') ?> GAJI POKOK</h2>
                <form method="POST" action="salary.php">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="id_gapok" value="<?= e($gapok_data['id_gapok'] ?? '') ?>">
                    <div class="mb-4">
                        <label for="id_jabatan" class="block mb-2 text-sm font-bold text-gray-700">Jabatan</label>
                        <select id="id_jabatan" name="id_jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                            <option value="">- Pilih Jabatan -</option>
                            <?php foreach($jabatan_list as $jabatan): ?>
                            <option value="<?= e($jabatan['id_jabatan']) ?>" <?= (isset($gapok_data) && $gapok_data['id_jabatan'] == $jabatan['id_jabatan']) ? 'selected' : '' ?>>
                                <?= e($jabatan['nama_jabatan']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="masa_kerja" class="block mb-2 text-sm font-bold text-gray-700">Masa Kerja (Tahun)</label>
                        <input type="number" id="masa_kerja" name="masa_kerja" value="<?= e($gapok_data['masa_kerja'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                    </div>
                    <div class="mb-6">
                        <label for="nominal" class="block mb-2 text-sm font-bold text-gray-700">Nominal Gaji Pokok (Rp)</label>
                        <input type="number" id="nominal" name="nominal" value="<?= e($gapok_data['nominal'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                    </div>
                    <div class="flex items-center justify-end space-x-4">
                        <a href="salary.php?action=list_gapok" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
                        <button type="submit" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] font-bold text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($action === 'new_payroll'): ?>
             <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
                <h2 class="text-2xl font-bold text-[#2e7d32] text-center mb-6">PENGAJUAN GAJI</h2>
                <form method="POST" action="salary.php?action=process_payroll_submission">
                    <?php csrf_input(); ?>
                    <div class="mb-4">
                        <label for="id_karyawan" class="block mb-2 text-sm font-bold text-gray-700">Nama Karyawan</label>
                         <select name="id_karyawan" id="id_karyawan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                            <option value="">- Pilih Karyawan -</option>
                             </select>
                    </div>
                    <div class="mb-6">
                        <label for="tgl_gaji" class="block mb-2 text-sm font-bold text-gray-700">Tanggal Gaji</label>
                        <input type="date" name="tgl_gaji" id="tgl_gaji" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#43a047]" required>
                    </div>
                     <div class="flex items-center justify-end space-x-4">
                        <a href="../index.php" class="bg-[#e53935] text-white px-4 py-2 rounded-md hover:bg-[#c62828] font-bold text-sm">Batal</a>
                        <button type="submit" class="bg-[#43a047] text-white px-4 py-2 rounded-md hover:bg-[#388e3c] font-bold text-sm">Selanjutnya</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
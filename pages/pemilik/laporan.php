<?php
$page_title = 'Laporan Gaji';
$current_page = 'laporan';
require_once __DIR__ . '/../../includes/functions.php';
require_login('pemilik');

$conn = db_connect();

// Ambil data untuk filter
$jabatan_list = $conn->query("SELECT Id_Jabatan, Nama_Jabatan FROM JABATAN ORDER BY Nama_Jabatan ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil filter dari GET request
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';
$filter_jabatan = $_GET['jabatan'] ?? '';
?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-3xl font-bold text-[#2e7d32]">Laporan Penggajian</h1>
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold shadow-sm">
            <i class="fa-solid fa-print mr-2"></i>Cetak Laporan
        </button>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8 no-print">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Filter Laporan</h3>
        <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="bulan" class="block text-sm font-medium text-gray-600 mb-1">Bulan</label>
                <select name="bulan" id="bulan" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">-- Semua Bulan --</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $filter_bulan == $i ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 10)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="tahun" class="block text-sm font-medium text-gray-600 mb-1">Tahun</label>
                <input type="number" name="tahun" id="tahun" placeholder="Cth: <?= date('Y') ?>" value="<?= e($filter_tahun) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label for="jabatan" class="block text-sm font-medium text-gray-600 mb-1">Jabatan</label>
                <select name="jabatan" id="jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">-- Semua Jabatan --</option>
                    <?php foreach ($jabatan_list as $jabatan): ?>
                        <option value="<?= e($jabatan['Id_Jabatan']) ?>" <?= $filter_jabatan == $jabatan['Id_Jabatan'] ? 'selected' : '' ?>><?= e($jabatan['Nama_Jabatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold">Terapkan</button>
                <a href="laporan.php" class="w-full text-center bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm font-semibold">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            Hasil Laporan
            <span class="text-base font-normal text-gray-600">
                <?php
                if ($filter_bulan && $filter_tahun) echo "untuk Periode " . date('F', mktime(0, 0, 0, $filter_bulan, 10)) . " " . $filter_tahun;
                elseif ($filter_tahun) echo "untuk Tahun " . $filter_tahun;
                if ($filter_jabatan) {
                    $nama_jabatan_terfilter = '';
                    foreach ($jabatan_list as $j) { if ($j['Id_Jabatan'] === $filter_jabatan) $nama_jabatan_terfilter = $j['Nama_Jabatan']; }
                    echo ($filter_bulan || $filter_tahun) ? " & " : "untuk ";
                    echo "Jabatan " . e($nama_jabatan_terfilter);
                }
                ?>
            </span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Tanggal Terima</th>
                        <th class="px-4 py-3 text-right">Gaji Kotor</th>
                        <th class="px-4 py-3 text-right">Total Potongan</th>
                        <th class="px-4 py-3 text-right">Gaji Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Bangun query dinamis berdasarkan filter
                    $sql = "
                        SELECT k.Nama_Karyawan, j.Nama_Jabatan, g.Tgl_Gaji, g.Gaji_Kotor, g.Total_Potongan, g.Gaji_Bersih
                        FROM GAJI g
                        JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
                        JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
                        WHERE g.Status = 'Disetujui'
                    ";
                    $params = [];
                    $types = '';

                    if ($filter_bulan) {
                        $sql .= " AND MONTH(g.Tgl_Gaji) = ?";
                        $params[] = $filter_bulan;
                        $types .= 'i';
                    }
                    if ($filter_tahun) {
                        $sql .= " AND YEAR(g.Tgl_Gaji) = ?";
                        $params[] = $filter_tahun;
                        $types .= 'i';
                    }
                    if ($filter_jabatan) {
                        $sql .= " AND j.Id_Jabatan = ?";
                        $params[] = $filter_jabatan;
                        $types .= 's';
                    }
                    $sql .= " ORDER BY g.Tgl_Gaji DESC, k.Nama_Karyawan ASC";
                    
                    $stmt = $conn->prepare($sql);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $no = 1;
                    $total_gaji_bersih = 0;

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $total_gaji_bersih += $row['Gaji_Bersih'];
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-3"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                        <td class="px-4 py-3"><?= e($row['Nama_Jabatan']) ?></td>
                        <td class="px-4 py-3"><?= e(date('d M Y', strtotime($row['Tgl_Gaji']))) ?></td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($row['Gaji_Kotor'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right text-red-600">- Rp <?= number_format($row['Total_Potongan'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">Rp <?= number_format($row['Gaji_Bersih'], 0, ',', '.') ?></td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-gray-500">Tidak ada data yang cocok dengan kriteria filter.</td>
                        </tr>
                    <?php 
                    endif;
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
                <?php if ($result->num_rows > 0): ?>
                <tfoot class="font-bold bg-gray-50">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right">Total Gaji Bersih Dikeluarkan:</td>
                        <td class="px-4 py-3 text-right text-green-800">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


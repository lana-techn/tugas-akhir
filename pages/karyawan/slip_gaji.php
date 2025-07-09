<?php
$page_title = 'Slip Gaji';
$current_page = 'slip_gaji';
// 1. SETUP
require_once __DIR__ . '/../../includes/functions.php';
require_login('karyawan');

$conn = db_connect();
$slip_gaji = null;
$detail_komponen = [];
$gaji_pokok_nominal = 0;

if (isset($_SESSION['user_id'])) {
    $id_pengguna = $_SESSION['user_id'];

    // Ambil Id_Karyawan dan Tgl_Awal_Kerja berdasarkan Id_Pengguna
    $stmt_karyawan = $conn->prepare("SELECT Id_Karyawan, Tgl_Awal_Kerja, Id_Jabatan FROM KARYAWAN WHERE Id_Pengguna = ?");
    $stmt_karyawan->bind_param("s", $id_pengguna);
    $stmt_karyawan->execute();
    $karyawan_data = $stmt_karyawan->get_result()->fetch_assoc();
    $stmt_karyawan->close();

    if ($karyawan_data) {
        $id_karyawan = $karyawan_data['Id_Karyawan'];
        $id_jabatan = $karyawan_data['Id_Jabatan'];
        $tgl_awal_kerja = new DateTime($karyawan_data['Tgl_Awal_Kerja']);

        // Ambil data gaji terbaru untuk karyawan ini
        $stmt_gaji = $conn->prepare("
            SELECT
                g.Id_Gaji, g.Tgl_Gaji, g.Total_Tunjangan, g.Total_Lembur, g.Total_Potongan, g.Gaji_Kotor, g.Gaji_Bersih,
                k.Nama_Karyawan, j.Nama_Jabatan
            FROM GAJI g
            JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
            JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
            WHERE g.Id_Karyawan = ?
            ORDER BY g.Tgl_Gaji DESC
            LIMIT 1
        ");
        $stmt_gaji->bind_param("s", $id_karyawan);
        $stmt_gaji->execute();
        $slip_gaji = $stmt_gaji->get_result()->fetch_assoc();
        $stmt_gaji->close();

        if ($slip_gaji) {
            // Hitung masa kerja dalam tahun
            $tgl_gaji = new DateTime($slip_gaji['Tgl_Gaji']);
            $masa_kerja_interval = $tgl_gaji->diff($tgl_awal_kerja);
            $masa_kerja_tahun = $masa_kerja_interval->y;

            // Ambil Gaji Pokok berdasarkan Jabatan dan Masa Kerja
            $stmt_gapok = $conn->prepare(
                "SELECT Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1"
            );
            $stmt_gapok->bind_param("si", $id_jabatan, $masa_kerja_tahun);
            $stmt_gapok->execute();
            $result_gapok = $stmt_gapok->get_result()->fetch_assoc();
            $gaji_pokok_nominal = $result_gapok['Nominal'] ?? 0;
            $stmt_gapok->close();


            // Ambil detail komponen gaji dari tabel DETAIL_GAJI
            $stmt_detail = $conn->prepare("
                SELECT
                    dg.Jumlah_Tunjangan, dg.Jumlah_Potongan, dg.Jumlah_Lembur,
                    t.Nama_Tunjangan, p.Nama_Potongan, l.Lama_Lembur
                FROM DETAIL_GAJI dg
                LEFT JOIN TUNJANGAN t ON dg.Id_Tunjangan = t.Id_Tunjangan
                LEFT JOIN POTONGAN p ON dg.Id_Potongan = p.Id_Potongan
                LEFT JOIN LEMBUR l ON dg.Id_Lembur = l.Id_Lembur
                WHERE dg.Id_Gaji = ?
            ");
            $stmt_detail->bind_param("s", $slip_gaji['Id_Gaji']);
            $stmt_detail->execute();
            $detail_komponen = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_detail->close();
        }
    }
}
$conn->close();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-3xl font-bold text-[#2e7d32]">Slip Gaji</h1>
        <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-semibold shadow-sm">
            <i class="fa-solid fa-print mr-2"></i>Cetak
        </button>
    </div>

    <?php if ($slip_gaji): ?>
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto border border-gray-200">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">SLIP GAJI KARYAWAN</h1>
                <p class="text-gray-600">Periode: <?= e(date('F Y', strtotime($slip_gaji['Tgl_Gaji']))) ?></p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 mb-6 text-sm">
                <div>
                    <p><strong>Nama Karyawan:</strong><span class="ml-2"><?= e($slip_gaji['Nama_Karyawan']) ?></span></p>
                    <p><strong>Jabatan:</strong><span class="ml-2"><?= e($slip_gaji['Nama_Jabatan']) ?></span></p>
                </div>
                <div>
                    <p><strong>ID Karyawan:</strong><span class="ml-2"><?= e($id_karyawan) ?></span></p>
                    <p><strong>Tanggal Pembayaran:</strong><span class="ml-2"><?= e(date('d M Y', strtotime($slip_gaji['Tgl_Gaji']))) ?></span></p>
                </div>
            </div>

            <hr class="my-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12">
                <div>
                    <h3 class="text-lg font-semibold text-green-700 mb-3">Pendapatan</h3>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr>
                                <td class="py-2">Gaji Pokok</td>
                                <td class="py-2 text-right">Rp <?= number_format($gaji_pokok_nominal, 2, ',', '.') ?></td>
                            </tr>
                            <?php foreach ($detail_komponen as $detail): ?>
                                <?php if (!empty($detail['Nama_Tunjangan']) && $detail['Jumlah_Tunjangan'] > 0): ?>
                                <tr>
                                    <td class="py-2">Tunjangan: <?= e($detail['Nama_Tunjangan']) ?></td>
                                    <td class="py-2 text-right">Rp <?= number_format($detail['Jumlah_Tunjangan'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                             <tr>
                                <td class="py-2">Total Lembur</td>
                                <td class="py-2 text-right">Rp <?= number_format($slip_gaji['Total_Lembur'], 2, ',', '.') ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="font-bold">
                             <tr class="bg-gray-50">
                                <td class="py-2.5">Total Pendapatan (Gaji Kotor)</td>
                                <td class="py-2.5 text-right">Rp <?= number_format($slip_gaji['Gaji_Kotor'], 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-700 mb-3">Potongan</h3>
                     <table class="w-full text-sm">
                        <tbody>
                            <?php foreach ($detail_komponen as $detail): ?>
                                <?php if (!empty($detail['Nama_Potongan']) && $detail['Jumlah_Potongan'] > 0): ?>
                                <tr>
                                    <td class="py-2">Potongan: <?= e($detail['Nama_Potongan']) ?></td>
                                    <td class="py-2 text-right">- Rp <?= number_format($detail['Jumlah_Potongan'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                         <tfoot class="font-bold">
                             <tr class="bg-gray-50">
                                <td class="py-2.5">Total Potongan</td>
                                <td class="py-2.5 text-right">- Rp <?= number_format($slip_gaji['Total_Potongan'], 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="mt-8 bg-green-50 p-4 rounded-lg text-right">
                <p class="text-sm text-gray-600">GAJI BERSIH (TAKE HOME PAY)</p>
                <p class="text-2xl font-bold text-green-800">Rp <?= number_format($slip_gaji['Gaji_Bersih'], 2, ',', '.') ?></p>
            </div>

        </div>
    <?php else: ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-700">Belum ada slip gaji yang tersedia untuk Anda.</p>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
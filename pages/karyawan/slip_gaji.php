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
$id_karyawan = '';

if (isset($_SESSION['user_id'])) {
    $id_pengguna = $_SESSION['user_id'];

    // Ambil Id_Karyawan, Tgl_Awal_Kerja, dan Id_Jabatan berdasarkan Id_Pengguna
    $stmt_karyawan = $conn->prepare("SELECT Id_Karyawan, Tgl_Awal_Kerja, Id_Jabatan FROM KARYAWAN WHERE Id_Pengguna = ?");
    $stmt_karyawan->bind_param("s", $id_pengguna);
    $stmt_karyawan->execute();
    $karyawan_data = $stmt_karyawan->get_result()->fetch_assoc();
    $stmt_karyawan->close();

    if ($karyawan_data) {
        $id_karyawan = $karyawan_data['Id_Karyawan'];
        $id_jabatan = $karyawan_data['Id_Jabatan'];
        $tgl_awal_kerja = new DateTime($karyawan_data['Tgl_Awal_Kerja']);

        // Ambil data gaji terbaru yang sudah disetujui untuk karyawan ini
        $stmt_gaji = $conn->prepare("
            SELECT
                g.Id_Gaji, g.Tgl_Gaji, g.Total_Tunjangan, g.Total_Lembur, g.Total_Potongan, g.Gaji_Kotor, g.Gaji_Bersih,
                k.Nama_Karyawan, j.Nama_Jabatan
            FROM GAJI g
            JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan
            JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
            WHERE g.Id_Karyawan = ? AND g.Status = 'Disetujui'
            ORDER BY g.Tgl_Gaji DESC
            LIMIT 1
        ");
        $stmt_gaji->bind_param("s", $id_karyawan);
        $stmt_gaji->execute();
        $slip_gaji = $stmt_gaji->get_result()->fetch_assoc();
        $stmt_gaji->close();

        if ($slip_gaji) {
            $tgl_gaji = new DateTime($slip_gaji['Tgl_Gaji']);
            $masa_kerja_tahun = $tgl_gaji->diff($tgl_awal_kerja)->y;

            $stmt_gapok = $conn->prepare(
                "SELECT Nominal FROM GAJI_POKOK WHERE Id_Jabatan = ? AND Masa_Kerja <= ? ORDER BY Masa_Kerja DESC LIMIT 1"
            );
            $stmt_gapok->bind_param("si", $id_jabatan, $masa_kerja_tahun);
            $stmt_gapok->execute();
            $result_gapok = $stmt_gapok->get_result()->fetch_assoc();
            $gaji_pokok_nominal = $result_gapok['Nominal'] ?? 0;
            $stmt_gapok->close();

            $stmt_detail = $conn->prepare("
                SELECT
                    dg.Id_Tunjangan, dg.Id_Potongan, dg.Id_Lembur,
                    dg.Jumlah_Tunjangan, dg.Jumlah_Potongan, dg.Jumlah_Lembur,
                    t.Nama_Tunjangan, p.Nama_Potongan, l.Nama_Lembur, l.Lama_Lembur
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
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Slip Gaji</h2>
            <p class="text-gray-500 text-sm">Rincian pendapatan dan potongan gaji Anda.</p>
        </div>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i>Cetak
            </button>
            <button onclick="cetakSlipPDF()" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i>Unduh PDF
            </button>
        </div>
    </div>

    <?php if ($slip_gaji): ?>
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-200">
            <div class="text-center mb-8 pb-6 border-b-2 border-dashed">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 font-poppins">SLIP GAJI KARYAWAN</h1>
                <p class="text-gray-600">Periode: <?= e(date('F Y', strtotime($slip_gaji['Tgl_Gaji']))) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 mb-8 text-sm">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-500">Nama Karyawan:</span>
                        <span class="font-semibold text-gray-800"><?= e($slip_gaji['Nama_Karyawan']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-500">Jabatan:</span>
                        <span class="font-semibold text-gray-800"><?= e($slip_gaji['Nama_Jabatan']) ?></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-500">ID Karyawan:</span>
                        <span class="font-semibold text-gray-800"><?= e($id_karyawan) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-500">Tanggal Pembayaran:</span>
                        <span class="font-semibold text-gray-800"><?= e(date('d M Y', strtotime($slip_gaji['Tgl_Gaji']))) ?></span>
                    </div>
                </div>
            </div>

            <hr class="my-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div>
                    <h3 class="text-lg font-bold text-green-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-down"></i>PENDAPATAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Gaji Pokok</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($gaji_pokok_nominal, 0, ',', '.') ?></span>
                        </div>
                        <?php foreach ($detail_komponen as $detail): ?>
                            <?php if (!empty($detail['Id_Tunjangan'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tunjangan: <?= e($detail['Nama_Tunjangan']) ?></span>
                                <span class="font-semibold text-gray-800">Rp <?= number_format($detail['Jumlah_Tunjangan'], 0, ',', '.') ?></span>
                            </div>
                            <?php elseif (!empty($detail['Id_Lembur'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Lembur (<?= e($detail['Lama_Lembur']) ?> jam)</span>
                                <span class="font-semibold text-gray-800">Rp <?= number_format($detail['Jumlah_Lembur'], 0, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Pendapatan (Gaji Kotor)</span>
                        <span>Rp <?= number_format($slip_gaji['Gaji_Kotor'], 0, ',', '.') ?></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-up"></i>POTONGAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                         <?php 
                         $ada_potongan = false;
                         foreach ($detail_komponen as $detail): ?>
                            <?php if (!empty($detail['Id_Potongan'])): $ada_potongan = true; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Potongan: <?= e($detail['Nama_Potongan']) ?></span>
                                <span class="font-semibold text-red-600">- Rp <?= number_format($detail['Jumlah_Potongan'], 0, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if(!$ada_potongan): ?>
                            <div class="flex justify-between text-gray-500">
                                <span>Tidak ada potongan</span>
                                <span>- Rp 0</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Potongan</span>
                        <span class="text-red-600">- Rp <?= number_format($slip_gaji['Total_Potongan'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-10 bg-green-50 p-4 rounded-lg text-center sm:text-right">
                <p class="text-sm font-semibold text-gray-600">GAJI BERSIH (TAKE HOME PAY)</p>
                <p class="text-3xl font-bold text-green-800">Rp <?= number_format($slip_gaji['Gaji_Bersih'], 0, ',', '.') ?></p>
            </div>

        </div>
    <?php else: ?>
        <div class="bg-white p-10 rounded-xl shadow-lg text-center border border-gray-200">
            <i class="fa-solid fa-folder-open text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-700">Belum Ada Data</h3>
            <p class="text-gray-500 mt-2">Slip gaji Anda akan tersedia di sini setelah proses penggajian selesai dan disetujui.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function cetakSlipPDF() {
    // Arahkan ke skrip PHP yang menghasilkan PDF
    window.open('karyawan/cetak_slip_gaji_pdf.php', '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
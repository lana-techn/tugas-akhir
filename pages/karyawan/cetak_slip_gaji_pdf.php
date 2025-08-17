<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('karyawan');

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();
$slip_data = null;
$presensi_data = null;
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    die("Error: ID Gaji tidak valid atau tidak ditemukan.");
}

// Ambil data slip gaji utama berdasarkan ID dari URL
$stmt_gaji = $conn->prepare(
    "SELECT g.*, k.Nama_Karyawan, k.Id_Karyawan as id_karyawan_db, k.Tgl_Awal_Kerja, j.Nama_Jabatan, dg.Nominal_Gapok 
     FROM GAJI g 
     JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
     JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
     LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
     WHERE g.Id_Gaji = ?"
);
$stmt_gaji->bind_param("s", $id_gaji);
$stmt_gaji->execute();
$slip_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$slip_data) {
    die("Data slip gaji dengan ID tersebut tidak ditemukan.");
}

// Ambil data presensi untuk periode gaji terkait
$bulan_nama_db = date('F', strtotime($slip_data['Tgl_Gaji']));
$bulan_map = [ "January" => "Januari", "February" => "Februari", "March" => "Maret", "April" => "April", "May" => "Mei", "June" => "Juni", "July" => "Juli", "August" => "Agustus", "September" => "September", "October" => "Oktober", "November" => "November", "December" => "Desember" ];
$bulan_gaji = $bulan_map[$bulan_nama_db];
$tahun_gaji = date('Y', strtotime($slip_data['Tgl_Gaji']));

$stmt_presensi = $conn->prepare("SELECT Hadir, Sakit, Izin, Alpha, Jam_Lembur, Uang_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
$stmt_presensi->bind_param("ssi", $slip_data['id_karyawan_db'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();

// Siapkan data untuk ditampilkan
$kehadiran_hari = $presensi_data['Hadir'] ?? 0;
$absensi_hari = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;

// Siapkan detail potongan
$gaji_pokok = $slip_data['Nominal_Gapok'] ?? 0;
$detail_potongan_display = [];

// Potongan BPJS Ketenagakerjaan (2%)
$potongan_bpjs = $gaji_pokok * 0.02;
if ($potongan_bpjs > 0) {
    $detail_potongan_display[] = ['nama' => 'Potongan BPJS Ketenagakerjaan (2%)', 'jumlah' => $potongan_bpjs];
}

// Potongan Absensi
$total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
if ($total_hari_tidak_hadir > 0) {
    $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
    $detail_potongan_display[] = ['nama' => "Potongan Absensi ({$total_hari_tidak_hadir} hari)", 'jumlah' => $potongan_absensi];
}

// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - ' . e($slip_data['Nama_Karyawan']) . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; color: #333; }
        .container { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 2rem; }
        .header-table { width: 100%; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .company-details { text-align: center; }
        .company-name { font-size: 1.5rem; font-weight: bold; }
        .company-address { font-size: 0.875rem; color: #64748b; }
        .title-section { text-align: center; margin-bottom: 1.5rem; }
        .title-section h1 { font-size: 1.5rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
        .title-section p { color: #64748b; margin: 0.25rem 0 0; }
        .details-table { width: 100%; margin-bottom: 1.5rem; }
        .details-table td { padding: 0.25rem 0.5rem 0.25rem 0; vertical-align: top; }
        .details-table .label { color: #64748b; }
        .section { margin-bottom: 1.5rem; }
        .section-title { font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 0.5rem; margin-bottom: 0.5rem; }
        .item-table { width: 100%; }
        .item-table td { padding: 0.5rem 0; }
        .item-table .amount { text-align: right; font-weight: bold; }
        .total-row { border-top: 1px solid #cbd5e1; font-weight: bold; }
        .summary-box { background-color: #f0fdf4; border-top: 4px solid #22c55e; padding: 1rem; margin-top: 2rem; }
        .summary-box .summary-table { width: 100%; }
        .summary-box .summary-label { font-size: 1.125rem; font-weight: bold; color: #166534; }
        .summary-box .summary-amount { font-size: 1.25rem; font-weight: bold; color: #166534; text-align: right; }
        .attendance-section { background-color: #f8fafc; padding: 1rem; margin: 1rem 0; border-radius: 0.5rem; }
        .attendance-grid { display: table; width: 100%; }
        .attendance-item { display: table-cell; text-align: center; padding: 0.5rem; }
        .attendance-number { font-weight: bold; font-size: 1.125rem; }
        .attendance-label { font-size: 0.75rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td class="company-details" style="vertical-align: middle;">
                    <div class="company-name">CV. KARYA WAHANA SENTOSA</div>
                    <div class="company-address">Jl. Imogiri Barat Km.17, Bungas, Jetis, Bantul</div>
                </td>
            </tr>
        </table>

        <div class="title-section">
            <h1>Slip Gaji Karyawan</h1>
            <p>Periode: ' . e(date('F Y', strtotime($slip_data['Tgl_Gaji']))) . '</p>
        </div>

        <table class="details-table">
            <tr>
                <td class="label" width="20%">Nama Karyawan</td><td width="30%">: ' . e($slip_data['Nama_Karyawan']) . '</td>
                <td class="label" width="20%">ID Karyawan</td><td width="30%">: ' . e($slip_data['id_karyawan_db']) . '</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td><td>: ' . e($slip_data['Nama_Jabatan']) . '</td>
                <td class="label">Tanggal Pembayaran</td><td>: ' . e(date('d M Y', strtotime($slip_data['Tgl_Gaji']))) . '</td>
            </tr>
        </table>

        <table width="100%">
            <tr>
                <td>
                    <div class="section">
                        <div class="section-title">PENDAPATAN</div>
                        <table class="item-table">
                            <tr><td>Gaji Pokok</td><td class="amount">Rp ' . number_format($slip_data['Nominal_Gapok'] ?? 0, 2, ',', '.') . '</td></tr>
                            <tr><td>Tunjangan</td><td class="amount">Rp ' . number_format($slip_data['Total_Tunjangan'], 2, ',', '.') . '</td></tr>
                            <tr><td>Lembur (' . e($jam_lembur) . ' jam)</td><td class="amount">Rp ' . number_format($presensi_data['Uang_Lembur'], 2, ',', '.') . '</td></tr>
                            <tr class="total-row"><td>Total Pendapatan</td><td class="amount">Rp ' . number_format($slip_data['Gaji_Kotor'], 2, ',', '.') . '</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="section">
                        <div class="section-title">POTONGAN</div>
                        <table class="item-table">';
                        if(!empty($detail_potongan_display)) {
                            foreach($detail_potongan_display as $p) {
                                $html .= '<tr><td>' . e($p['nama']) . '</td><td class="amount" style="color: #dc2626;">- Rp ' . number_format($p['jumlah'], 2, ',', '.') . '</td></tr>';
                            }
                        } else {
                            $html .= '<tr><td style="color: #64748b;">Tidak ada potongan</td><td class="amount">- Rp 0.00</td></tr>';
                        }
$html .= '              <tr class="total-row"><td style="color: #dc2626;">Total Potongan</td><td class="amount" style="color: #dc2626;">- Rp ' . number_format($slip_data['Total_Potongan'], 2, ',', '.') . '</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="attendance-section">
            <div class="section-title">RINCIAN KEHADIRAN</div>
            <div class="attendance-grid">
                <div class="attendance-item">
                    <div class="attendance-number" style="color: #059669;">' . $kehadiran_hari . '</div>
                    <div class="attendance-label">Hadir</div>
                </div>
                <div class="attendance-item">
                    <div class="attendance-number" style="color: #d97706;">' . ($presensi_data['Sakit'] ?? 0) . '</div>
                    <div class="attendance-label">Sakit</div>
                </div>
                <div class="attendance-item">
                    <div class="attendance-number" style="color: #2563eb;">' . ($presensi_data['Izin'] ?? 0) . '</div>
                    <div class="attendance-label">Izin</div>
                </div>
                <div class="attendance-item">
                    <div class="attendance-number" style="color: #dc2626;">' . ($presensi_data['Alpha'] ?? 0) . '</div>
                    <div class="attendance-label">Alpha</div>
                </div>
            </div>
        </div>

        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">GAJI BERSIH DITERIMA (TAKE HOME PAY)</td>
                    <td class="summary-amount">Rp ' . number_format($slip_data['Gaji_Bersih'], 2, ',', '.') . '</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Slip_Gaji_' . str_replace(' ', '_', $slip_data['Nama_Karyawan']) . '_' . date('Y_m', strtotime($slip_data['Tgl_Gaji'])) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>
<?php
require_once __DIR__ . '/../includes/functions.php';
// Cek login untuk admin atau pemilik
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['level'], ['Admin', 'Pemilik'])) {
    header('Location: '. BASE_URL . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    die("Error: ID Gaji tidak valid.");
}

// 1. Ambil data gaji, karyawan, dan jabatan
$stmt_gaji = $conn->prepare(
    "SELECT g.*, k.Nama_Karyawan, j.Nama_Jabatan, k.Tgl_Awal_Kerja, dg.Nominal_Gapok 
     FROM GAJI g 
     JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
     JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan 
     LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
     WHERE g.Id_Gaji = ?"
);
$stmt_gaji->bind_param("s", $id_gaji);
$stmt_gaji->execute();
$gaji_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$gaji_data) {
    die("Data gaji dengan ID tersebut tidak ditemukan.");
}

// 2. Ambil data presensi
$bulan_nama_db = date('F', strtotime($gaji_data['Tgl_Gaji']));
$bulan_map = [ "January" => "Januari", "February" => "Februari", "March" => "Maret", "April" => "April", "May" => "Mei", "June" => "Juni", "July" => "Juli", "August" => "Agustus", "September" => "September", "October" => "Oktober", "November" => "November", "December" => "Desember" ];
$bulan_gaji = $bulan_map[$bulan_nama_db];
$tahun_gaji = date('Y', strtotime($gaji_data['Tgl_Gaji']));

$stmt_presensi = $conn->prepare("SELECT Hadir, Sakit, Izin, Alpha, Jam_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
$stmt_presensi->bind_param("ssi", $gaji_data['Id_Karyawan'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();

// 3. Siapkan data untuk ditampilkan
$gaji_pokok = $gaji_data['Nominal_Gapok'] ?? 0;
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;

$detail_potongan_display = [];
$potongan_bpjs = $gaji_pokok * 0.02;
if ($potongan_bpjs > 0) {
    $detail_potongan_display[] = ['nama' => 'Potongan BPJS Ketenagakerjaan (2%)', 'jumlah' => $potongan_bpjs];
}
$total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
if ($total_hari_tidak_hadir > 0) {
    $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
    $detail_potongan_display[] = ['nama' => "Potongan Absensi ({$total_hari_tidak_hadir} hari)", 'jumlah' => $potongan_absensi];
}

$masa_kerja_text = (new DateTime($gaji_data['Tgl_Gaji']))->diff(new DateTime($gaji_data['Tgl_Awal_Kerja']))->format('%y tahun, %m bulan');


// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - ' . e($gaji_data['Nama_Karyawan']) . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; color: #333; }
        .container { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 2rem; }
        .header-table { width: 100%; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .company-details { text-align: right; }
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
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td style="text-align: left; vertical-align: middle;">
                    <div style="font-family: serif; font-size: 28px; font-weight: bold; color: #166534; line-height: 1;">KWaS</div>
                    <div style="font-size: 12px; color: #16a34a; margin-top: 2px;">Furniture manufacturer</div>
                </td>
                <td class="company-details" style="vertical-align: middle;">
                    <div class="company-name">CV. KARYA WAHANA SENTOSA</div>
                    <div class="company-address">Jl. Imogiri Barat Km.17, Bungas, Jetis, Bantul</div>
                </td>
            </tr>
        </table>

        <div class="title-section">
            <h1>Slip Gaji Karyawan</h1>
            <p>Periode: ' . e(date('F Y', strtotime($gaji_data['Tgl_Gaji']))) . '</p>
        </div>

        <table class="details-table">
            <tr>
                <td class="label" width="20%">Nama Karyawan</td><td width="30%">: ' . e($gaji_data['Nama_Karyawan']) . '</td>
                <td class="label" width="20%">ID Karyawan</td><td width="30%">: ' . e($gaji_data['Id_Karyawan']) . '</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td><td>: ' . e($gaji_data['Nama_Jabatan']) . '</td>
                <td class="label">Status Gaji</td><td>: ' . e($gaji_data['Status']) . '</td>
            </tr>
        </table>

        <table width="100%">
            <tr>
                <td width="50%" style="padding-right: 1rem;">
                    <div class="section">
                        <div class="section-title">PENDAPATAN</div>
                        <table class="item-table">
                            <tr><td>Gaji Pokok</td><td class="amount">Rp ' . number_format($gaji_pokok, 2, ',', '.') . '</td></tr>
                            <tr><td>Tunjangan</td><td class="amount">Rp ' . number_format($gaji_data['Total_Tunjangan'], 2, ',', '.') . '</td></tr>
                            <tr><td>Lembur (' . e($jam_lembur) . ' jam)</td><td class="amount">Rp ' . number_format($gaji_data['Total_Lembur'], 2, ',', '.') . '</td></tr>
                            <tr class="total-row"><td>Total Pendapatan</td><td class="amount">Rp ' . number_format($gaji_data['Gaji_Kotor'], 2, ',', '.') . '</td></tr>
                        </table>
                    </div>
                </td>
                <td width="50%" style="padding-left: 1rem;">
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
$html .= '              <tr class="total-row"><td style="color: #dc2626;">Total Potongan</td><td class="amount" style="color: #dc2626;">- Rp ' . number_format($gaji_data['Total_Potongan'], 2, ',', '.') . '</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">GAJI BERSIH DITERIMA</td>
                    <td class="summary-amount">Rp ' . number_format($gaji_data['Gaji_Bersih'], 2, ',', '.') . '</td>
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

$filename = 'Slip_Gaji_' . str_replace(' ', '_', $gaji_data['Nama_Karyawan']) . '_' . date('Y_m', strtotime($gaji_data['Tgl_Gaji'])) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>
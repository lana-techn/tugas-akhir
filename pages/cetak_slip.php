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
    "SELECT g.*, k.Nama_Karyawan, j.Nama_Jabatan, k.Tgl_Awal_Kerja, gp.Nominal as Gaji_Pokok 
     FROM GAJI g 
     JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
     JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan 
     LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
     LEFT JOIN GAJI_POKOK gp ON dg.Id_Gapok = gp.Id_Gapok
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

$stmt_presensi = $conn->prepare("SELECT Hadir, Sakit, Izin, Alpha, Jam_Lembur, Uang_Lembur FROM PRESENSI WHERE Id_Karyawan = ? AND Bulan = ? AND Tahun = ?");
$stmt_presensi->bind_param("ssi", $gaji_data['Id_Karyawan'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();

// Calculate overtime pay if not set
if ($presensi_data && ($presensi_data['Uang_Lembur'] == 0 || $presensi_data['Uang_Lembur'] === null) && $presensi_data['Jam_Lembur'] > 0) {
    $presensi_data['Uang_Lembur'] = $presensi_data['Jam_Lembur'] * 20000;
}
$conn->close();

// 3. Siapkan data untuk ditampilkan
$gaji_pokok = $gaji_data['Gaji_Pokok'] ?? 0;
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;

// Prioritize Total_Lembur from GAJI table, fallback to PRESENSI calculation
$uang_lembur = $gaji_data['Total_Lembur'] ?? 0;
if ($uang_lembur == 0 && $jam_lembur > 0) {
    // Fallback calculation if not stored in GAJI table
    $uang_lembur = $presensi_data['Uang_Lembur'] ?? ($jam_lembur * 20000);
}

$detail_potongan_display = [];
$potongan_bpjs = $gaji_pokok * 0.02;
if ($potongan_bpjs > 0) {
    $detail_potongan_display[] = ['nama' => 'Potongan BPJS Ketenagakerjaan', 'keterangan' => '(2%)', 'jumlah' => $potongan_bpjs];
}
$total_hari_tidak_hadir = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
if ($total_hari_tidak_hadir > 0) {
    $potongan_absensi = ($gaji_pokok * 0.03) * $total_hari_tidak_hadir;
    $detail_potongan_display[] = ['nama' => "Potongan Absensi", 'keterangan' => "({$total_hari_tidak_hadir} Hari)", 'jumlah' => $potongan_absensi];
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
        @page { margin: 15mm; }
        body { 
            font-family: "Arial", sans-serif; 
            font-size: 12px; 
            color: #000; 
            margin: 0; 
            padding: 0;
            line-height: 1.4;
        }
        .slip-container { 
            border: 2px solid #333; 
            width: 100%; 
            border-collapse: collapse;
            background: #fff;
        }
        .header-section {
            background: #fff;
            border-bottom: 2px solid #333;
            padding: 30px;
            text-align: center;
        }
        .company-name { 
            font-size: 28px; 
            font-weight: bold; 
            margin-bottom: 10px;
            color: #16a34a;
        }
        .company-address { 
            font-size: 14px; 
            color: #6b7280;
            margin-bottom: 20px; 
        }
        .slip-title { 
            font-size: 22px; 
            font-weight: bold; 
            margin: 20px 0 10px 0;
            color: #1f2937;
        }
        .period { 
            font-size: 14px;
            color: #6b7280;
        }
        .employee-info {
            padding: 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row td {
            border: 1px solid #333;
            padding: 15px;
            font-size: 13px;
        }
        .info-label {
            width: 50%;
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .section-header {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #15803d;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            text-align: left;
            border: 1px solid #333;
        }
        .section-header.deduction {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #dc2626;
        }
        .section-header.attendance {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
        }
        .item-row td {
            border: 1px solid #333;
            padding: 12px;
            font-size: 13px;
        }
        .item-row:hover {
            background-color: #f9fafb;
        }
        .item-desc {
            width: 66.67%;
            font-weight: 500;
            color: #374151;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            width: 33.33%;
            font-size: 13px;
            color: #1f2937;
        }
        .item-desc-left {
            width: 66.67%;
            font-weight: 500;
            color: #374151;
            text-align: left;
        }
        .total-row {
            background: #f3f4f6;
            font-weight: bold;
        }
        .total-row td {
            border: 2px solid #333;
            padding: 12px;
            font-size: 14px;
        }
        .total-income {
            background: #dcfce7;
        }
        .total-deduction {
            background: #fecaca;
        }
        .attendance-section {
            padding: 0;
        }
        .attendance-row {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-row td {
            border: 1px solid #333;
            padding: 20px;
            text-align: center;
            width: 25%;
        }
        .attendance-number {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 8px;
            color: #000;
        }
        .attendance-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
        }
        .final-amount {
            background: #f9fafb;
            color: #1f2937;
            padding: 25px;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #333;
        }
        .final-amount-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .final-amount-label {
            font-size: 20px;
            color: #1f2937;
        }
        .final-amount-number {
            font-size: 24px;
            color: #16a34a;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table class="slip-container">
        <!-- Header Perusahaan -->
        <tr>
            <td class="header-section">
                <div class="company-name">CV. KARYA WAHANA SENTOSA</div>
                <div class="company-address">Jl. Imogiri Barat, Km.17, Bungas, Jetis, Bantul</div>
                <div class="slip-title">SLIP GAJI KARYAWAN</div>
                <div class="period">Periode: ' . e(date('F Y', strtotime($gaji_data['Tgl_Gaji']))) . '</div>
            </td>
        </tr>
        
        <!-- Detail Karyawan -->
        <tr>
            <td class="employee-info">
                <table class="info-table">
                    <tr>
                        <td class="info-row info-label">
                            <div style="margin-bottom: 15px;"><strong>Nama Karyawan :</strong> ' . e($gaji_data['Nama_Karyawan']) . '</div>
                            <div><strong>Jabatan :</strong> ' . e($gaji_data['Nama_Jabatan']) . '</div>
                        </td>
                        <td class="info-row">
                            <div style="margin-bottom: 15px;"><strong>ID Karyawan :</strong> ' . e($gaji_data['Id_Karyawan']) . '</div>
                            <div><strong>Tanggal Pembayaran :</strong> ' . e(date('d F Y', strtotime($gaji_data['Tgl_Gaji']))) . '</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- PENDAPATAN -->
        <tr>
            <td class="section-header">
                PENDAPATAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table class="item-table">
                    <tr class="item-row">
                        <td class="item-desc-left">Gaji Pokok</td>
                        <td class="amount">Rp ' . number_format($gaji_pokok, 0, ',', '.') . ',00</td>
                    </tr>
                    <tr class="item-row">
                        <td class="item-desc-left">Tunjangan</td>
                        <td class="amount">Rp ' . number_format($gaji_data['Total_Tunjangan'], 0, ',', '.') . ',00</td>
                    </tr>
                    <tr class="item-row">
                        <td class="item-desc-left">Lembur <span style="color: #6b7280; font-size: 11px;">(' . e($jam_lembur) . ' Jam)</span></td>
                        <td class="amount">Rp ' . number_format($uang_lembur, 0, ',', '.') . ',00</td>
                    </tr>
                    <tr class="total-row total-income">
                        <td style="color: #16a34a;">Total Pendapatan</td>
                        <td class="amount" style="color: #16a34a;">Rp ' . number_format($gaji_data['Gaji_Kotor'], 0, ',', '.') . ',00</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- POTONGAN -->
        <tr>
            <td class="section-header deduction">
                POTONGAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table class="item-table">';
                
                if(!empty($detail_potongan_display)) {
                    foreach($detail_potongan_display as $p) {
                        $html .= '<tr class="item-row">
                            <td class="item-desc-left">
                                <div style="font-weight: 500; color: #374151;">' . e($p['nama']) . '</div>
                                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">' . e($p['keterangan']) . '</div>
                            </td>
                            <td class="amount" style="color: #dc2626;">Rp ' . number_format($p['jumlah'], 0, ',', '.') . ',00</td>
                        </tr>';
                    }
                } else {
                    $html .= '<tr class="item-row">
                        <td class="item-desc-left">Tidak ada potongan</td>
                        <td class="amount">Rp 0,00</td>
                    </tr>';
                }
                
$html .= '          <tr class="total-row total-deduction">
                        <td style="color: #dc2626;">Total Potongan</td>
                        <td class="amount" style="color: #dc2626;">Rp ' . number_format($gaji_data['Total_Potongan'], 0, ',', '.') . ',00</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- RINCIAN KEHADIRAN -->
        <tr>
            <td class="section-header attendance">
                RINCIAN KEHADIRAN
            </td>
        </tr>
        <tr>
            <td class="attendance-section">
                <table class="attendance-row">
                    <tr>
                        <td style="border: 1px solid #333; padding: 20px; text-align: center;">
                            <div class="attendance-number">' . ($presensi_data['Hadir'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Hadir</div>
                        </td>
                        <td style="border: 1px solid #333; padding: 20px; text-align: center;">
                            <div class="attendance-number">' . ($presensi_data['Sakit'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Sakit</div>
                        </td>
                        <td style="border: 1px solid #333; padding: 20px; text-align: center;">
                            <div class="attendance-number">' . ($presensi_data['Izin'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Izin</div>
                        </td>
                        <td style="border: 1px solid #333; padding: 20px; text-align: center;">
                            <div class="attendance-number">' . ($presensi_data['Alpha'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Alpha</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- GAJI BERSIH -->
        <tr>
            <td class="final-amount">
                <div class="final-amount-content">
                    <span class="final-amount-label">GAJI BERSIH DITERIMA (TAKE HOME PAY)</span>
                    <span class="final-amount-number">Rp ' . number_format($gaji_data['Gaji_Bersih'], 0, ',', '.') . ',00</span>
                </div>
            </td>
        </tr>
    </table>
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
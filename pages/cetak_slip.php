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
        @page { margin: 15mm; }
        body { 
            font-family: "Arial", sans-serif; 
            font-size: 11px; 
            color: #000; 
            margin: 0; 
            padding: 0;
            line-height: 1.4;
        }
        .slip-container { 
            border: 2px solid #000; 
            width: 100%; 
            border-collapse: collapse;
            background: #fff;
        }
        .header-section {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-bottom: 2px solid #000;
            padding: 25px;
            text-align: center;
        }
        .company-name { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 8px;
            color: #1e3a8a;
        }
        .company-address { 
            font-size: 12px; 
            color: #4b5563;
            margin-bottom: 15px; 
        }
        .slip-title { 
            font-size: 20px; 
            font-weight: bold; 
            margin: 15px 0 8px 0;
            color: #1f2937;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
        }
        .period { 
            font-size: 12px;
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
            border: 1px solid #000;
            padding: 12px;
            font-size: 12px;
        }
        .info-label {
            width: 130px;
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        .section-header {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 15px;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            border: 1px solid #000;
        }
        .section-header.income {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .section-header.deduction {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .section-header.attendance {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
        }
        .item-row td {
            border: 1px solid #000;
            padding: 10px;
            font-size: 12px;
        }
        .item-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            width: 160px;
            font-size: 13px;
        }
        .total-row {
            background: #e5e7eb;
            font-weight: bold;
        }
        .total-row td {
            border: 2px solid #000;
            padding: 12px;
            font-size: 14px;
        }
        .attendance-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-cell {
            border: 1px solid #000;
            padding: 18px;
            text-align: center;
            width: 25%;
        }
        .attendance-number {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 8px;
        }
        .attendance-label {
            font-size: 10px;
            color: #6b7280;
            font-weight: 500;
        }
        .final-amount {
            background: #f3f4f6;
            color: #1f2937;
            padding: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid #000;
        }
        .final-amount-number {
            font-size: 22px;
            margin-top: 10px;
            color: #111827;
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
        </tr>';
        
$html .= '
        <!-- Detail Karyawan -->
        <tr>
            <td class="employee-info">
                <table class="info-table">
                    <tr>
                        <td class="info-row info-label">Nama Karyawan</td>
                        <td class="info-row" style="width: 30%;">' . e($gaji_data['Nama_Karyawan']) . '</td>
                        <td class="info-row info-label">ID Karyawan</td>
                        <td class="info-row">' . e($gaji_data['Id_Karyawan']) . '</td>
                    </tr>
                    <tr>
                        <td class="info-row info-label">Jabatan</td>
                        <td class="info-row">' . e($gaji_data['Nama_Jabatan']) . '</td>
                        <td class="info-row info-label">Tanggal Pembayaran</td>
                        <td class="info-row">' . e(date('d F Y', strtotime($gaji_data['Tgl_Gaji']))) . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- PENDAPATAN -->
        <tr>
            <td class="section-header income">
                💰 PENDAPATAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table class="item-table">
                    <tr class="item-row">
                        <td>Gaji Pokok</td>
                        <td class="amount">Rp ' . number_format($gaji_pokok, 0, ',', '.') . '</td>
                    </tr>
                    <tr class="item-row">
                        <td>Tunjangan</td>
                        <td class="amount">Rp ' . number_format($gaji_data['Total_Tunjangan'], 0, ',', '.') . '</td>
                    </tr>
                    <tr class="item-row">
                        <td>Lembur (' . e($jam_lembur) . ' Jam @ Rp 20.000)</td>
                        <td class="amount">Rp ' . number_format($uang_lembur, 0, ',', '.') . '</td>
                    </tr>
                    <tr class="total-row">
                        <td style="color: #38a169;">Total Pendapatan</td>
                        <td class="amount" style="color: #38a169;">Rp ' . number_format($gaji_data['Gaji_Kotor'], 0, ',', '.') . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- POTONGAN -->
        <tr>
            <td class="section-header deduction">
                📉 POTONGAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table class="item-table">';
                
                if(!empty($detail_potongan_display)) {
                    $row_count = 0;
                    foreach($detail_potongan_display as $p) {
                        $html .= '<tr class="item-row">
                            <td>' . e($p['nama']) . '</td>
                            <td class="amount">-Rp ' . number_format($p['jumlah'], 0, ',', '.') . '</td>
                        </tr>';
                        $row_count++;
                    }
                } else {
                    $html .= '<tr class="item-row">
                        <td>Tidak ada potongan</td>
                        <td class="amount">Rp 0</td>
                    </tr>';
                }
                
$html .= '          <tr class="total-row">
                        <td style="color: #e53e3e;">Total Potongan</td>
                        <td class="amount" style="color: #e53e3e;">-Rp ' . number_format($gaji_data['Total_Potongan'], 0, ',', '.') . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- RINCIAN KEHADIRAN -->
        <tr>
            <td class="section-header attendance">
                📅 RINCIAN KEHADIRAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table class="attendance-grid">
                    <tr>
                        <td class="attendance-cell" style="background: #f0fff4;">
                            <div class="attendance-number" style="color: #38a169;">' . ($presensi_data['Hadir'] ?? 0) . '</div>
                            <div class="attendance-label">Hari Hadir</div>
                        </td>
                        <td class="attendance-cell" style="background: #fffbf0;">
                            <div class="attendance-number" style="color: #d69e2e;">' . ($presensi_data['Sakit'] ?? 0) . '</div>
                            <div class="attendance-label">Hari Sakit</div>
                        </td>
                        <td class="attendance-cell" style="background: #f0f8ff;">
                            <div class="attendance-number" style="color: #3182ce;">' . ($presensi_data['Izin'] ?? 0) . '</div>
                            <div class="attendance-label">Hari Izin</div>
                        </td>
                        <td class="attendance-cell" style="background: #fff5f5;">
                            <div class="attendance-number" style="color: #e53e3e;">' . ($presensi_data['Alpha'] ?? 0) . '</div>
                            <div class="attendance-label">Hari Alpha</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- GAJI BERSIH -->
        <tr>
            <td class="final-amount">
                <div>GAJI BERSIH DITERIMA (TAKE HOME PAY)</div>
                <div class="final-amount-number">Rp ' . number_format($gaji_data['Gaji_Bersih'], 0, ',', '.') . '</div>
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
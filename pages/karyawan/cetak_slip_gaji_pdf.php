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
    "SELECT g.*, k.Nama_Karyawan, k.Id_Karyawan as id_karyawan_db, k.Tgl_Awal_Kerja, j.Nama_Jabatan, gp.Nominal as Gaji_Pokok 
     FROM GAJI g 
     JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan 
     JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan
     LEFT JOIN DETAIL_GAJI dg ON g.Id_Gaji = dg.Id_Gaji
     LEFT JOIN GAJI_POKOK gp ON dg.Id_Gapok = gp.Id_Gapok
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

// Calculate overtime pay if not set
if ($presensi_data && ($presensi_data['Uang_Lembur'] == 0 || $presensi_data['Uang_Lembur'] === null) && $presensi_data['Jam_Lembur'] > 0) {
    $presensi_data['Uang_Lembur'] = $presensi_data['Jam_Lembur'] * 20000;
}
$conn->close();

// Siapkan data untuk ditampilkan
$kehadiran_hari = $presensi_data['Hadir'] ?? 0;
$absensi_hari = ($presensi_data['Sakit'] ?? 0) + ($presensi_data['Izin'] ?? 0) + ($presensi_data['Alpha'] ?? 0);
$jam_lembur = $presensi_data['Jam_Lembur'] ?? 0;

// Siapkan detail potongan
$gaji_pokok = $slip_data['Gaji_Pokok'] ?? 0;
$uang_lembur = $presensi_data['Uang_Lembur'] ?? 0;
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
        .header-row td {
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
        .detail-row td {
            border: 1px solid #000;
            padding: 10px;
            font-size: 12px;
        }
        .detail-label {
            width: 120px;
            background: #f7fafc;
            font-weight: bold;
            color: #2d3748;
        }
        .section-header {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 12px;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            border: 1px solid #000;
        }
        .section-header.income {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }
        .section-header.deduction {
            background: linear-gradient(135deg, #f56565, #e53e3e);
        }
        .item-row td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }
        .item-row:nth-child(even) {
            background-color: #f7fafc;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            width: 150px;
        }
        .total-row {
            background: #e2e8f0;
            font-weight: bold;
        }
        .total-row td {
            border: 2px solid #000;
            padding: 10px;
            font-size: 13px;
        }
        .attendance-header {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
            color: white;
            padding: 12px;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            border: 1px solid #000;
        }
        .attendance-row td {
            border: 1px solid #000;
            padding: 15px;
            text-align: center;
            font-size: 11px;
        }
        .attendance-number {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
        }
        .attendance-label {
            font-size: 10px;
            color: #4a5568;
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
    </style>
</head>
<body>
    <table class="slip-container">
        <!-- Header Perusahaan -->
        <tr class="header-row">
            <td>
                <div class="company-name">CV. KARYA WAHANA SENTOSA</div>
                <div class="company-address">Jl. Imogiri Barat, Km.17, Bungas, Jetis, Bantul</div>
                <div class="slip-title">SLIP GAJI KARYAWAN</div>
                <div class="period">Periode: ' . e(date('F Y', strtotime($slip_data['Tgl_Gaji']))) . '</div>
            </td>
        </tr>
        
        <!-- Detail Karyawan -->
        <tr>
            <td style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="detail-row detail-label">Nama Karyawan :</td>
                        <td class="detail-row" style="width: 30%;">' . e($slip_data['Nama_Karyawan']) . '</td>
                        <td class="detail-row detail-label">ID Karyawan :</td>
                        <td class="detail-row">' . e($slip_data['id_karyawan_db']) . '</td>
                    </tr>
                    <tr>
                        <td class="detail-row detail-label">Jabatan :</td>
                        <td class="detail-row">' . e($slip_data['Nama_Jabatan']) . '</td>
                        <td class="detail-row detail-label">Tanggal Pembayaran :</td>
                        <td class="detail-row">' . e(date('d/m/y', strtotime($slip_data['Tgl_Gaji']))) . '</td>
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
                <table style="width: 100%; border-collapse: collapse;">
                    <tr class="item-row">
                        <td style="border: 1px solid #000; padding: 6px;">Gaji Pokok</td>
                        <td class="amount" style="border: 1px solid #000; padding: 6px;">Rp ' . number_format($slip_data['Gaji_Pokok'] ?? 0, 0, ',', '.') . '</td>
                    </tr>
                    <tr class="item-row">
                        <td style="border: 1px solid #000; padding: 6px;">Tunjangan</td>
                        <td class="amount" style="border: 1px solid #000; padding: 6px;">Rp ' . number_format($slip_data['Total_Tunjangan'], 0, ',', '.') . '</td>
                    </tr>
                    <tr class="item-row">
                        <td style="border: 1px solid #000; padding: 6px;">Lembur (' . e($jam_lembur) . ' Jam)</td>
                        <td class="amount" style="border: 1px solid #000; padding: 6px;">Rp ' . number_format($uang_lembur, 0, ',', '.') . '</td>
                    </tr>
                    <tr class="total-row">
                        <td style="border: 2px solid #000; padding: 6px; font-weight: bold;">Total Pendapatan</td>
                        <td class="amount" style="border: 2px solid #000; padding: 6px; font-weight: bold;">Rp ' . number_format($slip_data['Gaji_Kotor'], 0, ',', '.') . '</td>
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
                <table style="width: 100%; border-collapse: collapse;">';
                
                if(!empty($detail_potongan_display)) {
                    $row_count = 0;
                    foreach($detail_potongan_display as $p) {
                        $bg_style = ($row_count % 2 == 0) ? 'background-color: #f8f8f8;' : '';
                        $html .= '<tr style="' . $bg_style . '">
                            <td style="border: 1px solid #000; padding: 6px;">' . e($p['nama']) . '</td>
                            <td class="amount" style="border: 1px solid #000; padding: 6px;">Rp ' . number_format($p['jumlah'], 0, ',', '.') . '</td>
                        </tr>';
                        $row_count++;
                    }
                } else {
                    $html .= '<tr style="background-color: #f8f8f8;">
                        <td style="border: 1px solid #000; padding: 6px;">Tidak ada potongan</td>
                        <td class="amount" style="border: 1px solid #000; padding: 6px;">Rp 0</td>
                    </tr>';
                }
                
$html .= '          <tr class="total-row">
                        <td style="border: 2px solid #000; padding: 6px; font-weight: bold; color: #cc0000;">Total Potongan</td>
                        <td class="amount" style="border: 2px solid #000; padding: 6px; font-weight: bold; color: #cc0000;">Rp ' . number_format($slip_data['Total_Potongan'], 0, ',', '.') . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- RINCIAN KEHADIRAN -->
        <tr>
            <td class="attendance-header">
                📅 RINCIAN KEHADIRAN
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr class="attendance-row">
                        <td style="border: 1px solid #000; width: 25%;">
                            <div class="attendance-number">' . $kehadiran_hari . '</div>
                            <div class="attendance-label">Jumlah Hadir</div>
                        </td>
                        <td style="border: 1px solid #000; width: 25%;">
                            <div class="attendance-number">' . ($presensi_data['Sakit'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Sakit</div>
                        </td>
                        <td style="border: 1px solid #000; width: 25%;">
                            <div class="attendance-number">' . ($presensi_data['Izin'] ?? 0) . '</div>
                            <div class="attendance-label">Jumlah Izin</div>
                        </td>
                        <td style="border: 1px solid #000; width: 25%;">
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
                <div>GAJI BERSIH DITERIMA (TAKE HOME PAY)</div>
                <div style="font-size: 22px; margin-top: 10px; color: #111827;">Rp ' . number_format($slip_data['Gaji_Bersih'], 0, ',', '.') . '</div>
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

$filename = 'Slip_Gaji_' . str_replace(' ', '_', $slip_data['Nama_Karyawan']) . '_' . date('Y_m', strtotime($slip_data['Tgl_Gaji'])) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>
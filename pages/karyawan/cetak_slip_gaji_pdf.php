<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin('karyawan');

// Include DomPDF library
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();
$slip_gaji = null;
$detail_komponen = [];
$gaji_pokok_nominal = 0;
$id_karyawan = '';
$masa_kerja_tahun = 0;

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
                    dg.Id_Tunjangan, dg.Id_Potongan, dg.Id_Lembur,
                    dg.Jumlah_Tunjangan, dg.Jumlah_Potongan, dg.Jumlah_Lembur,
                    dg.Nominal_Gapok,
                    t.Nama_Tunjangan, p.Nama_Potongan, l.Nama_Lembur, l.Lama_Lembur, l.Upah_Lembur
                FROM DETAIL_GAJI dg
                LEFT JOIN TUNJANGAN t ON dg.Id_Tunjangan = t.Id_Tunjangan
                LEFT JOIN POTONGAN p ON dg.Id_Potongan = p.Id_Potongan
                LEFT JOIN LEMBUR l ON dg.Id_Lembur = l.Id_Lembur
                WHERE dg.Id_Gaji = ?
            ");
            $stmt_detail->bind_param("i", $slip_gaji['Id_Gaji']);
            $stmt_detail->execute();
            $detail_komponen = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_detail->close();
        }
    }
}
$conn->close();

// Jika tidak ada data slip gaji, redirect atau tampilkan error
if (!$slip_gaji) {
    die("Tidak ada data slip gaji yang tersedia.");
}

// Pisahkan komponen berdasarkan jenis
$tunjangan_list = [];
$lembur_list = [];
$potongan_list = [];

foreach ($detail_komponen as $detail) {
    if (!empty($detail['Id_Tunjangan'])) {
        $tunjangan_list[] = $detail;
    } elseif (!empty($detail['Id_Lembur'])) {
        $lembur_list[] = $detail;
    } elseif (!empty($detail['Id_Potongan'])) {
        $potongan_list[] = $detail;
    }
}

// Hitung kehadiran dan absensi (dummy data untuk contoh)
$kehadiran_hari = 22; // Bisa diambil dari database
$absensi_hari = 0; // Bisa diambil dari database

// Buat HTML untuk PDF sesuai dengan desain gambar
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        body { 
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 11px; 
            margin: 0; 
            padding: 0;
            line-height: 1.2;
        }
        .slip-container {
            border: 2px solid #000;
            padding: 0;
            margin: 0;
            width: 100%;
            height: auto;
        }
        .header {
            border: 1px solid #000;
            text-align: center;
            padding: 15px 10px;
            margin: 0;
            background-color: #f9f9f9;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #4a7c59;
            margin: 0 0 5px 0;
            line-height: 1;
            letter-spacing: 1px;
        }
        .logo-subtitle {
            font-size: 12px;
            color: #7a9b7a;
            margin: 0 0 8px 0;
            font-style: italic;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0 2px 0;
            line-height: 1.2;
            color: #333;
        }
        .company-address {
            font-size: 10px;
            margin: 2px 0;
            line-height: 1.2;
            color: #666;
        }
        .slip-title {
            border: 1px solid #000;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
            background-color: #f5f5f5;
            margin: 0;
        }
        .info-section {
            border: 1px solid #000;
            padding: 8px;
            margin: 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
            font-size: 10px;
        }
        .info-left {
            width: 50%;
        }
        .info-right {
            width: 50%;
        }
        .attendance-row {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .attendance-row td {
            border-right: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }
        .section-header {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            border: 1px solid #000;
            margin: 0;
            font-size: 11px;
        }
        .gaji-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .gaji-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }
        .gaji-table .label-col {
            width: 70%;
            background-color: #f9f9f9;
        }
        .gaji-table .value-col {
            width: 30%;
            text-align: left;
            padding-left: 5px;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .final-total {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 11px;
        }
        .button-section {
            text-align: right;
            margin-top: 15px;
            padding: 10px;
        }
        .btn {
            border: 1px solid #000;
            padding: 5px 15px;
            margin-left: 10px;
            background-color: #f5f5f5;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="slip-container">
        <!-- Header dengan logo text dan info perusahaan -->
        <div class="header">
            <div class="logo-text">KWaS</div>
            <div class="logo-subtitle">Furniture manufacturer</div>
            <div class="company-name">CV KARYA WAHANA SENTOSA</div>
            <div class="company-address">Jl. Imogiri Barat Km. 17</div>
            <div class="company-address">Bungas, Jetis, Bantul, Yogyakarta</div>
        </div>
        
        <!-- Judul Slip -->
        <div class="slip-title">SLIP GAJI</div>
        
        <!-- Info Karyawan -->
        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td class="info-left">Nama : ' . htmlspecialchars($slip_gaji['Nama_Karyawan']) . '</td>
                    <td class="info-right">Bulan : ' . date('F', strtotime($slip_gaji['Tgl_Gaji'])) . '</td>
                </tr>
                <tr>
                    <td class="info-left">Jabatan : ' . htmlspecialchars($slip_gaji['Nama_Jabatan']) . '</td>
                    <td class="info-right">Tahun : ' . date('Y', strtotime($slip_gaji['Tgl_Gaji'])) . '</td>
                </tr>
                <tr>
                    <td class="info-left">Masa Kerja : ' . $masa_kerja_tahun . ' tahun</td>
                    <td class="info-right">Id Karyawan : ' . htmlspecialchars($id_karyawan) . '</td>
                </tr>
            </table>
        </div>
        
        <!-- Kehadiran dan Absensi -->
        <table class="gaji-table">
            <tr class="attendance-row">
                <td style="width: 25%;">Kehadiran</td>
                <td style="width: 25%;">: ' . $kehadiran_hari . ' hari</td>
                <td style="width: 25%;">Absensi</td>
                <td style="width: 25%;">: ' . $absensi_hari . ' hari</td>
            </tr>
        </table>
        
        <!-- Bagian GAJI -->
        <div class="section-header">GAJI</div>
        <table class="gaji-table">
            <tr>
                <td class="label-col">Gaji Pokok</td>
                <td class="value-col">Rp ' . number_format($gaji_pokok_nominal, 0, ',', '.') . '</td>
            </tr>
        </table>
        
        <!-- Bagian TUNJANGAN -->
        <div class="section-header">TUNJANGAN</div>
        <table class="gaji-table">';

if (!empty($tunjangan_list)) {
    foreach ($tunjangan_list as $tunjangan) {
        $html .= '
            <tr>
                <td class="label-col">' . htmlspecialchars($tunjangan['Nama_Tunjangan']) . '</td>
                <td class="value-col">Rp ' . number_format($tunjangan['Jumlah_Tunjangan'], 0, ',', '.') . '</td>
            </tr>';
    }
} else {
    $html .= '
            <tr>
                <td class="label-col">Tunjangan</td>
                <td class="value-col">Rp 0</td>
            </tr>';
}

$html .= '
        </table>
        
        <!-- Bagian LEMBUR -->
        <div class="section-header">LEMBUR</div>
        <table class="gaji-table">';

if (!empty($lembur_list)) {
    foreach ($lembur_list as $lembur) {
        $html .= '
            <tr>
                <td class="label-col">Lembur (' . htmlspecialchars($lembur['Lama_Lembur']) . ' jam)</td>
                <td class="value-col">Rp ' . number_format($lembur['Jumlah_Lembur'], 0, ',', '.') . '</td>
            </tr>';
    }
} else {
    $html .= '
            <tr>
                <td class="label-col">Lembur</td>
                <td class="value-col">Rp 0</td>
            </tr>';
}

$html .= '
        </table>
        
        <!-- Bagian POTONGAN -->
        <div class="section-header">POTONGAN</div>
        <table class="gaji-table">';

if (!empty($potongan_list)) {
    foreach ($potongan_list as $potongan) {
        $html .= '
            <tr>
                <td class="label-col">' . htmlspecialchars($potongan['Nama_Potongan']) . '</td>
                <td class="value-col">Rp ' . number_format($potongan['Jumlah_Potongan'], 0, ',', '.') . '</td>
            </tr>';
    }
} else {
    // Tampilkan potongan default jika tidak ada
    $html .= '
            <tr>
                <td class="label-col">Potongan BPJS</td>
                <td class="value-col">Rp 0</td>
            </tr>
            <tr>
                <td class="label-col">Potongan Absensi</td>
                <td class="value-col">Rp 0</td>
            </tr>';
}

$html .= '
            <tr class="total-row">
                <td class="label-col">Total Potongan</td>
                <td class="value-col">Rp ' . number_format($slip_gaji['Total_Potongan'], 0, ',', '.') . '</td>
            </tr>
        </table>
        
        <!-- Gaji Kotor -->
        <table class="gaji-table">
            <tr class="total-row">
                <td class="label-col">Gaji Kotor</td>
                <td class="value-col">Rp ' . number_format($slip_gaji['Gaji_Kotor'], 0, ',', '.') . '</td>
            </tr>
        </table>
        
        <!-- Gaji Bersih -->
        <table class="gaji-table">
            <tr class="final-total">
                <td class="label-col">GAJI BERSIH</td>
                <td class="value-col">Rp ' . number_format($slip_gaji['Gaji_Bersih'], 0, ',', '.') . '</td>
            </tr>
        </table>
        
        <!-- Tombol Cetak dan Kembali -->
        <div class="button-section">
            <span class="btn">Cetak</span>
            <span class="btn">Kembali</span>
        </div>
    </div>
</body>
</html>';

// Generate PDF dengan DomPDF
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Slip_Gaji_' . $slip_gaji['Nama_Karyawan'] . '_' . date('Y-m-d', strtotime($slip_gaji['Tgl_Gaji'])) . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));

// Untuk preview HTML (comment jika ingin langsung download PDF)
// echo $html;
?>


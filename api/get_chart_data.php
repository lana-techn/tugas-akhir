<?php
// Set header ke JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

$conn = db_connect();

$type = $_GET['type'] ?? '';
$response = ['labels' => [], 'values' => []];

if ($type === 'karyawan_per_jabatan') {
    $sql = "
        SELECT j.Nama_Jabatan, COUNT(k.Id_Karyawan) as jumlah
        FROM JABATAN j
        LEFT JOIN KARYAWAN k ON j.Id_Jabatan = k.Id_Jabatan AND k.Status = 'Aktif'
        GROUP BY j.Nama_Jabatan
        ORDER BY jumlah DESC
    ";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $response['labels'][] = $row['Nama_Jabatan'];
        $response['values'][] = (int)$row['jumlah'];
    }
} 
elseif ($type === 'gaji_per_bulan') {
    // Siapkan data untuk 6 bulan terakhir
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime("first day of -$i months");
        $bulan_label = $date->format('F Y');
        $bulan_db = $date->format('m');
        $tahun_db = $date->format('Y');

        $response['labels'][] = $bulan_label;

        // Query total gaji untuk bulan tersebut
        $sql = "
            SELECT SUM(Gaji_Bersih) as total_gaji 
            FROM GAJI 
            WHERE Status = 'Disetujui' AND MONTH(Tgl_Gaji) = ? AND YEAR(Tgl_Gaji) = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $bulan_db, $tahun_db);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $response['values'][] = (float)($result['total_gaji'] ?? 0);
        $stmt->close();
    }
}

$conn->close();

// Kembalikan response sebagai JSON
echo json_encode($response);
exit;
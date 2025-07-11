<?php
// Test cases
$test_periods = [
    '2025-07',  // Valid period
    '2025-13',  // Invalid month
    '2025-00',  // Invalid month
    '2025-7',   // Invalid format
    '202507',   // Invalid format
];

foreach ($test_periods as $periode) {
    echo "Testing period: $periode\n";
    
    // First validation (regex)
    if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
        echo "Failed initial regex validation\n\n";
        continue;
    }
    
    // Second validation (checkdate)
    $periode_parts = explode('-', $periode);
    if (count($periode_parts) !== 2 || !checkdate($periode_parts[1], 1, $periode_parts[0])) {
        echo "Failed checkdate validation\n\n";
        continue;
    }
    
    // Date calculation
    $tgl_gaji = date('Y-m-d', strtotime($periode . '-01 last day of this month'));
    echo "Calculated date: $tgl_gaji\n\n";
}
?>

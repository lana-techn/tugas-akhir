<?php
// Define test mode
define('TEST_MODE', true);
// Prevent headers from being sent
ob_start();
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
$conn = db_connect();
if (!$conn) {
    die("Database connection failed\n");
}
echo "Database connection successful\n";

// Set admin session
$_SESSION['user_role'] = 'admin';
$_SESSION['csrf_token'] = 'test_token';

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'ajukan_gaji' => '1',
    'Id_Karyawan' => 'KR001',
    'periode' => '2025-07',
    'Gaji_Kotor' => '5000000',
    'Total_Tunjangan' => '1000000',
    'Total_Lembur' => '500000',
    'Total_Potongan' => '250000',
'Gaji_Bersih' => '6250000',
    'csrf_token' => 'test_token'
];

// Debug variables
echo "POST data:\n";
print_r($_POST);
echo "\n";

// Debug session
echo "Session data:\n";
print_r($_SESSION);
echo "\n";

// Create a test function
function test_payroll_submission() {
    try {
        // Enable error reporting and logging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', '/tmp/php_errors.log');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        // Include the payroll process file
        ob_start();
        include __DIR__ . '/payroll_process.php';
        $output = ob_get_clean();
        
        echo "Test completed successfully!\n";
        echo "Output: " . $output . "\n";
        
    } catch (Exception $e) {
        echo "Error occurred: " . $e->getMessage() . "\n";
    }
}

// Run the test
test_payroll_submission();
?>

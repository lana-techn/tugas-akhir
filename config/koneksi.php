<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_penggajian";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Authentication Functions
function checkLogin($requiredLevel = null) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['level']) || empty($_SESSION['level'])) {
        return false;
    }
    
    if ($requiredLevel !== null && strtolower($_SESSION['level']) !== strtolower($requiredLevel)) {
        return false;
    }
    
    return true;
}

function redirectToLogin() {
    // Get current directory to determine redirect path
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    
    if ($currentDir === 'karyawan') {
        header("Location: ../auth/login.php");
    } else {
        header("Location: auth/login.php");
    }
    exit;
}

function requireLogin($requiredLevel = null) {
    if (!checkLogin($requiredLevel)) {
        redirectToLogin();
    }
}

function getUserInfo() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return [
        'level' => $_SESSION['level'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ];
}

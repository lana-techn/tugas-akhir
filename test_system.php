<!DOCTYPE html>
<html>
<head>
    <title>Test System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
    </style>
</head>
<body>
    <h1>Test System Penggajian</h1>
    
    <div class="test-box info">
        <h3>Database Connection Test</h3>
        <?php
        try {
            require_once 'config/koneksi.php';
            echo "<p class='success'>✓ Database connection successful</p>";
            
            // Test query
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM pengguna");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                echo "<p class='success'>✓ Found " . $row['count'] . " users in database</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box info">
        <h3>Authentication Functions Test</h3>
        <?php
        session_start();
        
        echo "<p>Current session status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "</p>";
        
        if (isset($_SESSION['level'])) {
            echo "<p class='success'>✓ User is logged in as: " . $_SESSION['level'] . "</p>";
            echo "<p>Email: " . ($_SESSION['email'] ?? 'Not set') . "</p>";
            
            $userInfo = getUserInfo();
            echo "<p>getUserInfo() returns: " . json_encode($userInfo) . "</p>";
            
            echo "<p>checkLogin(): " . (checkLogin() ? "TRUE" : "FALSE") . "</p>";
            echo "<p>checkLogin('admin'): " . (checkLogin('admin') ? "TRUE" : "FALSE") . "</p>";
            echo "<p>checkLogin('pemilik'): " . (checkLogin('pemilik') ? "TRUE" : "FALSE") . "</p>";
            echo "<p>checkLogin('karyawan'): " . (checkLogin('karyawan') ? "TRUE" : "FALSE") . "</p>";
        } else {
            echo "<p class='error'>✗ No user logged in</p>";
        }
        ?>
    </div>
    
    <div class="test-box info">
        <h3>Test Login Credentials</h3>
        <table border="1" style="border-collapse: collapse; width: 100%;">
            <tr>
                <th>Level</th>
                <th>Email</th>
                <th>Password</th>
            </tr>
            <tr>
                <td>Admin</td>
                <td>admin123@gmail.com</td>
                <td>admin123</td>
            </tr>
            <tr>
                <td>Pemilik</td>
                <td>pemilik1@gmail.com</td>
                <td>pemilik123</td>
            </tr>
            <tr>
                <td>Karyawan</td>
                <td>karyawan1@gmail.com</td>
                <td>karyawan123</td>
            </tr>
        </table>
    </div>
    
    <div class="test-box info">
        <h3>Navigation Links</h3>
        <p><a href="auth/login.php">Go to Login Page</a></p>
        <p><a href="index.php">Admin Dashboard</a></p>
        <p><a href="index_pemilik.php">Pemilik Dashboard</a></p>
        <p><a href="karyawan/index_karyawan.php">Karyawan Dashboard</a></p>
        <p><a href="auth/logout.php">Logout</a></p>
    </div>
</body>
</html>

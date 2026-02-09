<?php
/**
 * Direct Admin Password Reset
 * Edit the database settings below and run this file
 */

// ====== EDIT THESE SETTINGS ======
$db_host = 'localhost';
$db_name = 'billing';
$db_user = 'billing';
$db_pass = 'Billing123';
// =================================

echo "=== Direct Admin Password Reset ===\n\n";

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "[✓] Connected to database\n";
    
    // Reset password to 'admin'
    $newPassword = 'admin';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    
    if ($stmt->rowCount() > 0) {
        echo "[SUCCESS] Admin password has been reset!\n\n";
        echo "================================\n";
        echo "  Username: admin\n";
        echo "  Password: admin\n";
        echo "================================\n\n";
        echo "<a href='login.php' class='btn btn-primary btn-lg'>Go to Login</a>";
    } else {
        echo "[ERROR] Admin user not found!\n";
        echo "Make sure the schema has been run first.\n";
        echo "Run: php schema.php\n";
    }
    
} catch (PDOException $e) {
    echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database settings:\n";
    echo "- Host: $db_host\n";
    echo "- Database: $db_name\n";
    echo "- User: $db_user\n";
}
?>

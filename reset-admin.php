<?php
/**
 * Reset Admin Password Script
 * Access this file directly to reset the admin password
 */

require_once 'config.php';
require_once 'database.php';

echo "=== Reset Admin Password ===\n\n";

try {
    $db = Database::getBillingDB();
    
    // Reset password to 'admin123'
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    
    if ($stmt->rowCount() > 0) {
        echo "[SUCCESS] Admin password has been reset!\n\n";
        echo "New credentials:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n\n";
        echo "<a href='login.php' class='btn btn-primary'>Go to Login</a>";
    } else {
        echo "[ERROR] Admin user not found!\n";
        echo "Please run the schema first: php schema.php\n";
    }
    
} catch (PDOException $e) {
    echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    echo "\nMake sure your database is configured correctly in config.php\n";
}
?>

<?php
/**
 * Simple Test and Setup Script
 */

echo "<h1>ISP Billing - Setup & Test</h1>";

$db_host = 'localhost';
$db_name = 'billing';
$db_user = 'billing';
$db_pass = 'Billing123';

echo "<h3>Testing Database Connection...</h3>";

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connected to database: {$db_name}</p>";
    
    // Check if admin_users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>✗ Table 'admin_users' does not exist!</p>";
        echo "<h3>Creating tables...</h3>";
        
        // Create admin_users table
        $db->exec("
            CREATE TABLE admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'manager', 'support') DEFAULT 'support',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color: green;'>✓ Created admin_users table</p>";
        
        // Create customers table
        $db->exec("
            CREATE TABLE customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address TEXT,
                nid_number VARCHAR(20),
                status ENUM('active', 'inactive', 'suspended', 'disconnected') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color: green;'>✓ Created customers table</p>";
        
        // Create packages table
        $db->exec("
            CREATE TABLE packages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                download_speed INT DEFAULT 0,
                upload_speed INT DEFAULT 0,
                bandwidth_limit BIGINT DEFAULT 0,
                price DECIMAL(10,2) NOT NULL,
                billing_cycle ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color: green;'>✓ Created packages table</p>";
        
        // Create other tables...
        $db->exec("CREATE TABLE IF NOT EXISTS invoices (id INT AUTO_INCREMENT PRIMARY KEY, invoice_number VARCHAR(50) NOT NULL UNIQUE, customer_id INT NOT NULL, package_id INT NOT NULL, billing_period_start DATE NOT NULL, billing_period_end DATE NOT NULL, amount DECIMAL(10,2) NOT NULL, tax_amount DECIMAL(10,2) DEFAULT 0, total_amount DECIMAL(10,2) NOT NULL, status ENUM('pending', 'paid', 'cancelled', 'overdue') DEFAULT 'pending', due_date DATE NOT NULL, paid_date DATE, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
        echo "<p style='color: green;'>✓ Created invoices table</p>";
        
        $db->exec("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, invoice_id INT NOT NULL, customer_id INT NOT NULL, amount DECIMAL(10,2) NOT NULL, payment_method ENUM('cash', 'bank_transfer', 'bkash', 'nagad', 'card', 'other') DEFAULT 'cash', transaction_id VARCHAR(100), payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, received_by INT, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        echo "<p style='color: green;'>✓ Created payments table</p>";
        
        $db->exec("CREATE TABLE IF NOT EXISTS nas (id INT AUTO_INCREMENT PRIMARY KEY, nasname VARCHAR(100) NOT NULL, shortname VARCHAR(50), type VARCHAR(30) DEFAULT 'other', ports INT DEFAULT 0, secret VARCHAR(60) NOT NULL, server VARCHAR(64), community VARCHAR(50), description VARCHAR(200), status ENUM('active', 'inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
        echo "<p style='color: green;'>✓ Created nas table</p>";
        
        $db->exec("CREATE TABLE IF NOT EXISTS customer_packages (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, package_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE, status ENUM('active', 'expired', 'cancelled') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        echo "<p style='color: green;'>✓ Created customer_packages table</p>";
        
        // Create admin user with password 'admin'
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admin_users (username, password, email, full_name, role, status) VALUES ('admin', '{$hashedPassword}', 'admin@isp.com', 'System Administrator', 'admin', 'active')");
        echo "<p style='color: green;'>✓ Created admin user (username: admin, password: admin)</p>";
        
        // Insert sample packages
        $db->exec("INSERT IGNORE INTO packages (name, description, download_speed, upload_speed, bandwidth_limit, price, billing_cycle) VALUES 
        ('Basic 5Mbps', 'Basic internet plan', 5120, 1024, 50000000000, 500.00, 'monthly'),
        ('Standard 10Mbps', 'Standard plan', 10240, 2048, 100000000000, 800.00, 'monthly'),
        ('Premium 20Mbps', 'Premium plan', 20480, 4096, 200000000000, 1200.00, 'monthly')");
        echo "<p style='color: green;'>✓ Created sample packages</p>";
        
        echo "<hr><h2 style='color: green;'>Setup Complete!</h2>";
        
    } else {
        echo "<p style='color: green;'>✓ Table 'admin_users' exists</p>";
        
        // Check admin user
        $stmt = $db->query("SELECT * FROM admin_users WHERE username = 'admin'");
        if ($stmt->rowCount() == 0) {
            echo "<p style='color: orange;'>Creating admin user...</p>";
            $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
            $db->exec("INSERT INTO admin_users (username, password, email, full_name, role, status) VALUES ('admin', '{$hashedPassword}', 'admin@isp.com', 'System Administrator', 'admin', 'active')");
            echo "<p style='color: green;'>✓ Admin user created</p>";
        } else {
            echo "<p style='color: green;'>✓ Admin user exists</p>";
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>User: " . $user['username'] . "</p>";
            echo "<p>Email: " . $user['email'] . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Quick Links:</h3>";
    echo "<p><a href='login.php'>Login Page</a></p>";
    echo "<p><a href='dashboard.php'>Dashboard</a></p>";
    echo "<p><a href='debug.php'>Debug Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<h3>To fix this:</h3>";
    echo "<ol>";
    echo "<li>Make sure MariaDB is running</li>";
    echo "<li>Create the databases:</li>";
    echo "<pre>mysql -u root -p\nCREATE DATABASE billing;\nCREATE DATABASE radius;\nGRANT ALL PRIVILEGES ON billing.* TO 'billing'@'localhost' IDENTIFIED BY 'Billing123';\nGRANT ALL PRIVILEGES ON radius.* TO 'billing'@'localhost' IDENTIFIED BY 'Billing123';\nFLUSH PRIVILEGES;</pre>";
    echo "</ol>";
}
?>

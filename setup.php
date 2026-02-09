<?php
/**
 * Database Setup Script
 * Run this file to initialize the database
 */

echo "=== ISP Billing System Setup ===\n\n";

$step = intval($_GET['step'] ?? 1);

switch ($step) {
    case 1:
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>ISP Billing Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 50px; }
        .setup-box { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="setup-box">
        <h3 class="text-center mb-4">ISP Billing System Setup</h3>
        
        <div class="alert alert-info">
            <strong>Before running setup:</strong><br>
            1. Make sure MariaDB is running<br>
            2. Create the databases:<br>
            <code>CREATE DATABASE billing;</code><br>
            <code>CREATE DATABASE radius;</code>
        </div>
        
        <form method="POST" action="setup.php?step=2">
            <div class="mb-3">
                <label class="form-label">Database Host</label>
                <input type="text" class="form-control" name="db_host" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Billing DB Name</label>
                <input type="text" class="form-control" name="db_name" value="billing" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Billing DB User</label>
                <input type="text" class="form-control" name="db_user" value="billing" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Billing DB Password</label>
                <input type="password" class="form-control" name="db_pass" value="Billing123" required>
            </div>
            <div class="mb-3">
                <label class="form-label">RADIUS DB Name</label>
                <input type="text" class="form-control" name="radius_db_name" value="radius" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Run Setup</button>
        </form>
        
        <hr>
        
        <div class="text-center">
            <p><strong>Default Login After Setup:</strong></p>
            <p>Username: <code>admin</code></p>
            <p>Password: <code>admin123</code></p>
        </div>
    </div>
</body>
</html>
        <?php
        break;
        
    case 2:
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? 'billing';
        $db_user = $_POST['db_user'] ?? 'billing';
        $db_pass = $_POST['db_pass'] ?? 'Billing123';
        $radius_db_name = $_POST['radius_db_name'] ?? 'radius';
        
        echo "Testing database connection...\n";
        
        try {
            // Connect to billing DB
            $billingDb = new PDO(
                "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "[✓] Connected to billing database\n";
            
            // Connect to RADIUS DB
            $radiusDb = new PDO(
                "mysql:host={$db_host};dbname={$radius_db_name};charset=utf8mb4",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "[✓] Connected to RADIUS database\n";
            
            // Create tables
            echo "\nCreating billing tables...\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS admin_users (
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
            echo "[✓] admin_users table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS customers (
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
            echo "[✓] customers table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS packages (
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
            echo "[✓] packages table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS customer_packages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    package_id INT NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE,
                    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
                )
            ");
            echo "[✓] customer_packages table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS invoices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_number VARCHAR(50) NOT NULL UNIQUE,
                    customer_id INT NOT NULL,
                    package_id INT NOT NULL,
                    billing_period_start DATE NOT NULL,
                    billing_period_end DATE NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    tax_amount DECIMAL(10,2) DEFAULT 0,
                    total_amount DECIMAL(10,2) NOT NULL,
                    status ENUM('pending', 'paid', 'cancelled', 'overdue') DEFAULT 'pending',
                    due_date DATE NOT NULL,
                    paid_date DATE,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
                )
            ");
            echo "[✓] invoices table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS payments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method ENUM('cash', 'bank_transfer', 'bkash', 'nagad', 'card', 'other') DEFAULT 'cash',
                    transaction_id VARCHAR(100),
                    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    received_by INT,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                    FOREIGN KEY (received_by) REFERENCES admin_users(id) ON DELETE SET NULL
                )
            ");
            echo "[✓] payments table\n";
            
            $billingDb->exec("
                CREATE TABLE IF NOT EXISTS nas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nasname VARCHAR(100) NOT NULL,
                    shortname VARCHAR(50),
                    type VARCHAR(30) DEFAULT 'other',
                    ports INT DEFAULT 0,
                    secret VARCHAR(60) NOT NULL,
                    server VARCHAR(64),
                    community VARCHAR(50),
                    description VARCHAR(200),
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "[✓] nas table\n";
            
            // Create admin user with password 'admin123'
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $billingDb->exec("
                INSERT IGNORE INTO admin_users (username, password, email, full_name, role, status)
                VALUES ('admin', '{$hashedPassword}', 'admin@isp.com', 'System Administrator', 'admin', 'active')
            ");
            echo "[✓] Admin user created (username: admin, password: admin123)\n";
            
            // Insert sample packages
            $billingDb->exec("
                INSERT IGNORE INTO packages (name, description, download_speed, upload_speed, bandwidth_limit, price, billing_cycle) VALUES
                ('Basic 5Mbps', 'Basic internet plan with 5Mbps speed', 5120, 1024, 50000000000, 500.00, 'monthly'),
                ('Standard 10Mbps', 'Standard internet plan with 10Mbps speed', 10240, 2048, 100000000000, 800.00, 'monthly'),
                ('Premium 20Mbps', 'Premium internet plan with 20Mbps speed', 20480, 4096, 200000000000, 1200.00, 'monthly'),
                ('Business 50Mbps', 'Business internet plan with 50Mbps speed', 51200, 10240, 500000000000, 3000.00, 'monthly'),
                ('Enterprise 100Mbps', 'Enterprise internet plan with 100Mbps speed', 102400, 20480, 1000000000000, 5000.00, 'monthly')
            ");
            echo "[✓] Sample packages created\n";
            
            // Create RADIUS tables if not exist
            echo "\nCreating RADIUS tables...\n";
            
            $radiusDb->exec("
                CREATE TABLE IF NOT EXISTS radcheck (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '==',
                    value VARCHAR(253) NOT NULL
                )
            ");
            echo "[✓] radcheck table\n";
            
            $radiusDb->exec("
                CREATE TABLE IF NOT EXISTS radreply (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '=',
                    value VARCHAR(253) NOT NULL
                )
            ");
            echo "[✓] radreply table\n";
            
            $radiusDb->exec("
                CREATE TABLE IF NOT EXISTS nas (
                    id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    nasname VARCHAR(128) NOT NULL,
                    shortname VARCHAR(32) DEFAULT NULL,
                    type VARCHAR(30) DEFAULT 'other',
                    ports INT(5) DEFAULT NULL,
                    secret VARCHAR(60) NOT NULL,
                    server VARCHAR(64) DEFAULT NULL,
                    community VARCHAR(50) DEFAULT NULL,
                    description VARCHAR(200) DEFAULT 'RADIUS Client',
                    PRIMARY KEY (id),
                    KEY nasname (nasname)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
            echo "[✓] RADIUS nas table\n";
            
            echo "\n=================================\n";
            echo "[SUCCESS] Setup completed!\n";
            echo "=================================\n\n";
            echo "<a href='login.php' class='btn btn-success btn-lg'>Go to Login Page</a>\n";
            
        } catch (PDOException $e) {
            echo "[ERROR] Database error: " . $e->getMessage() . "\n";
            echo "\nPlease check your database settings and try again.\n";
        }
        break;
}
?>

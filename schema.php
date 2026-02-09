<?php
/**
 * Database Schema for ISP Billing System
 * Run this file once to create all required tables
 */

require_once 'config.php';
require_once 'database.php';

class Schema {
    
    public static function createBillingTables() {
        $db = Database::getBillingDB();
        
        // Admin Users Table
        $db->exec("
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
        
        // Customers Table
        $db->exec("
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
        
        // Packages Table
        $db->exec("
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
        
        // Customer Packages (Subscriptions)
        $db->exec("
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
        
        // Invoices Table
        $db->exec("
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
        
        // Payments Table
        $db->exec("
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
        
        // NAS Table
        $db->exec("
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
        
        // Payment Settings Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default admin user
        $db->exec("
            INSERT IGNORE INTO admin_users (username, password, email, full_name, role, status)
            VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@isp.com', 'System Administrator', 'admin', 'active')
        ");
        
        echo "Billing database tables created successfully!\n";
    }
    
    public static function createRadiusTables() {
        $db = Database::getRadiusDB();
        
        // Check if radcheck table exists, if not create it
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('radcheck', $tables)) {
            // Create FreeRADIUS tables (standard schema)
            $db->exec("
                CREATE TABLE IF NOT EXISTS radcheck (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '==',
                    value VARCHAR(253) NOT NULL
                )
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS radreply (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '=',
                    value VARCHAR(253) NOT NULL
                )
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS radgroupcheck (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    groupname VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '==',
                    value VARCHAR(253) NOT NULL
                )
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS radgroupreply (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    groupname VARCHAR(64) NOT NULL,
                    attribute VARCHAR(64) NOT NULL,
                    op CHAR(1) NOT NULL DEFAULT '=',
                    value VARCHAR(253) NOT NULL
                )
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS usergroup (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    groupname VARCHAR(64) NOT NULL,
                    priority INT NOT NULL DEFAULT 1
                )
            ");
            
            $db->exec("
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
                    -- added_by int(11) default '0',
                    -- added_date datetime default NULL,
                    -- updated_by int(11) default NULL,
                    -- updated_date datetime default NULL
                    PRIMARY KEY (id),
                    KEY nasname (nasname)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS radacct (
                    radacctid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    acctsessionid VARCHAR(64) NOT NULL,
                    acctuniqueid VARCHAR(32) NOT NULL,
                    username VARCHAR(64) NOT NULL,
                    groupname VARCHAR(64) NOT NULL,
                    realm VARCHAR(64) DEFAULT '',
                    nasipaddress VARCHAR(45) NOT NULL,
                    nasportid VARCHAR(15) DEFAULT NULL,
                    nasporttype VARCHAR(32) DEFAULT NULL,
                    acctstarttime DATETIME DEFAULT NULL,
                    acctstoptime DATETIME DEFAULT NULL,
                    acctsessiontime INT(11) UNSIGNED DEFAULT NULL,
                    acctauthentic VARCHAR(32) DEFAULT NULL,
                    connectinfo_start VARCHAR(50) DEFAULT NULL,
                    connectinfo_stop VARCHAR(50) DEFAULT NULL,
                    acctinputoctets BIGINT UNSIGNED DEFAULT NULL,
                    acctoutputoctets BIGINT UNSIGNED DEFAULT NULL,
                    calledstationid VARCHAR(50) NOT NULL,
                    callingstationid VARCHAR(50) NOT NULL,
                    acctterminatecause VARCHAR(32) NOT NULL,
                    servicetype VARCHAR(32) DEFAULT NULL,
                    framedprotocol VARCHAR(32) DEFAULT NULL,
                    framedipaddress VARCHAR(45) NOT NULL,
                    --  added_by int(11) default '0',
                    --  added_date datetime default NULL,
                    --  updated_by int(11) default NULL,
                    --  updated_date datetime default NULL
                    PRIMARY KEY (radacctid),
                    UNIQUE KEY acctuniqueid (acctuniqueid),
                    KEY username (username),
                    KEY framedipaddress (framedipaddress),
                    KEY acctsessiontime (acctsessiontime),
                    KEY acctstarttime (acctstarttime),
                    KEY acctstoptime (acctstoptime),
                    KEY nasipaddress (nasipaddress)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS radpostauth (
                    id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(64) NOT NULL,
                    pass VARCHAR(64) NOT NULL,
                    reply VARCHAR(32) NOT NULL,
                    authdate DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
            
            echo "FreeRADIUS database tables created successfully!\n";
        } else {
            echo "FreeRADIUS tables already exist.\n";
        }
    }
    
    public static function seedSampleData() {
        $db = Database::getBillingDB();
        
        // Check if packages already exist
        $count = $db->query("SELECT COUNT(*) FROM packages")->fetchColumn();
        
        if ($count == 0) {
            // Sample packages
            $db->exec("
                INSERT INTO packages (name, description, download_speed, upload_speed, bandwidth_limit, price, billing_cycle) VALUES
                ('Basic 5Mbps', 'Basic internet plan with 5Mbps speed', 5120, 1024, 50000000000, 500.00, 'monthly'),
                ('Standard 10Mbps', 'Standard internet plan with 10Mbps speed', 10240, 2048, 100000000000, 800.00, 'monthly'),
                ('Premium 20Mbps', 'Premium internet plan with 20Mbps speed', 20480, 4096, 200000000000, 1200.00, 'monthly'),
                ('Business 50Mbps', 'Business internet plan with 50Mbps speed', 51200, 10240, 500000000000, 3000.00, 'monthly'),
                ('Enterprise 100Mbps', 'Enterprise internet plan with 100Mbps speed', 102400, 20480, 1000000000000, 5000.00, 'monthly')
            ");
            
            echo "Sample packages added successfully!\n";
        }
    }
}

// Run schema creation
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_FILENAME']) === 'schema.php') {
    try {
        echo "Creating billing database tables...\n";
        Schema::createBillingTables();
        
        echo "Creating FreeRADIUS database tables...\n";
        Schema::createRadiusTables();
        
        echo "Seeding sample data...\n";
        Schema::seedSampleData();
        
        echo "\nDatabase setup completed successfully!\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

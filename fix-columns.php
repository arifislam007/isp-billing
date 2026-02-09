<?php
/**
 * Fix All Missing Columns in Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "Fixing database columns...\n\n";

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fix packages table
    echo "Checking packages table...\n";
    $columns = $db->query("SHOW COLUMNS FROM packages")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('nas_id', $columns)) {
        $db->exec("ALTER TABLE packages ADD COLUMN nas_id INT DEFAULT 0 AFTER status");
        echo "  - Added nas_id column\n";
    } else {
        echo "  - nas_id already exists\n";
    }
    
    if (!in_array('speed_down', $columns)) {
        $db->exec("ALTER TABLE packages ADD COLUMN speed_down VARCHAR(50) DEFAULT NULL AFTER nas_id");
        echo "  - Added speed_down column\n";
    } else {
        echo "  - speed_down already exists\n";
    }
    
    if (!in_array('speed_up', $columns)) {
        $db->exec("ALTER TABLE packages ADD COLUMN speed_up VARCHAR(50) DEFAULT NULL AFTER speed_down");
        echo "  - Added speed_up column\n";
    } else {
        echo "  - speed_up already exists\n";
    }
    
    if (!in_array('billing_cycle', $columns)) {
        $db->exec("ALTER TABLE packages ADD COLUMN billing_cycle ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly' AFTER price");
        echo "  - Added billing_cycle column\n";
    } else {
        echo "  - billing_cycle already exists\n";
    }
    
    if (!in_array('radgroupreply', $columns)) {
        $db->exec("ALTER TABLE packages ADD COLUMN radgroupreply TEXT DEFAULT NULL AFTER speed_up");
        echo "  - Added radgroupreply column\n";
    } else {
        echo "  - radgroupreply already exists\n";
    }
    
    // Fix customers table
    echo "\nChecking customers table...\n";
    $columns = $db->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('package_id', $columns)) {
        $db->exec("ALTER TABLE customers ADD COLUMN package_id INT DEFAULT 0 AFTER status");
        echo "  - Added package_id column\n";
    } else {
        echo "  - package_id already exists\n";
    }
    
    if (!in_array('conn_type', $columns)) {
        $db->exec("ALTER TABLE customers ADD COLUMN conn_type VARCHAR(20) DEFAULT 'pppoe' AFTER package_id");
        echo "  - Added conn_type column\n";
    } else {
        echo "  - conn_type already exists\n";
    }
    
    if (!in_array('router_ip', $columns)) {
        $db->exec("ALTER TABLE customers ADD COLUMN router_ip VARCHAR(50) DEFAULT NULL AFTER conn_type");
        echo "  - Added router_ip column\n";
    } else {
        echo "  - router_ip already exists\n";
    }
    
    if (!in_array('mac_address', $columns)) {
        $db->exec("ALTER TABLE customers ADD COLUMN mac_address VARCHAR(50) DEFAULT NULL AFTER router_ip");
        echo "  - Added mac_address column\n";
    } else {
        echo "  - mac_address already exists\n";
    }
    
    echo "\nAll columns fixed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

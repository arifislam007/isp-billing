<?php
/**
 * Debug Script - Run this to identify issues
 */

echo "<h1>ISP Billing System Debug</h1>";

// 1. Check PHP Version
echo "<h3>PHP Version</h3>";
echo "PHP " . phpversion() . "<br>";

// 2. Check Extensions
echo "<h3>Required Extensions</h3>";
$extensions = ['PDO', 'pdo_mysql', 'session', 'openssl'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "✓ Loaded" : "✗ Missing";
    echo "{$ext}: {$status}<br>";
}

// 3. Check Database Connection
echo "<h3>Database Connection</h3>";
$errors = [];

try {
    $dsn = "mysql:host=localhost;dbname=billing;charset=utf8mb4";
    $db = new PDO($dsn, 'billing', 'Billing123', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connected to billing database<br>";
} catch (PDOException $e) {
    echo "✗ Billing DB Error: " . $e->getMessage() . "<br>";
    $errors[] = "Billing DB: " . $e->getMessage();
}

try {
    $dsn = "mysql:host=localhost;dbname=radius;charset=utf8mb4";
    $db = new PDO($dsn, 'billing', 'Billing123', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connected to radius database<br>";
} catch (PDOException $e) {
    echo "✗ RADIUS DB Error: " . $e->getMessage() . "<br>";
    $errors[] = "RADIUS DB: " . $e->getMessage();
}

// 4. Check Tables
echo "<h3>Tables Check</h3>";
try {
    $dsn = "mysql:host=localhost;dbname=billing;charset=utf8mb4";
    $db = new PDO($dsn, 'billing', 'Billing123');
    
    $tables = ['admin_users', 'customers', 'packages', 'invoices', 'payments', 'nas'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ {$table} exists<br>";
        } else {
            echo "✗ {$table} MISSING<br>";
            $errors[] = "Table {$table} is missing";
        }
    }
    
    // Check admin user
    $stmt = $db->query("SELECT * FROM admin_users WHERE username = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "<br>✓ Admin user exists<br>";
        echo "  - ID: " . $admin['id'] . "<br>";
        echo "  - Email: " . $admin['email'] . "<br>";
        echo "  - Role: " . $admin['role'] . "<br>";
    } else {
        echo "<br>✗ Admin user NOT FOUND<br>";
        $errors[] = "Admin user not found";
    }
    
} catch (PDOException $e) {
    echo "Error checking tables: " . $e->getMessage() . "<br>";
}

// 5. Session Test
echo "<h3>Session Test</h3>";
session_start();
$_SESSION['test'] = 'working';
if ($_SESSION['test'] === 'working') {
    echo "✓ Sessions are working<br>";
} else {
    echo "✗ Sessions NOT working<br>";
    $errors[] = "Sessions not working";
}

// 6. Errors Summary
echo "<h3>Errors Found: " . count($errors) . "</h3>";
if (!empty($errors)) {
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Solutions</h3>";
echo "<ol>";
echo "<li><strong>Schema not run:</strong> Run <code>php schema.php</code> in terminal</li>";
echo "<li><strong>Database not created:</strong> Run: <br>
    <code>CREATE DATABASE billing;</code><br>
    <code>CREATE DATABASE radius;</code></li>";
echo "<li><strong>Wrong database credentials:</strong> Edit config.php with correct settings</li>";
echo "<li><strong>Database user permissions:</strong> Grant all privileges to 'billing' user</li>";
echo "</ol>";

echo "<h3>Quick Fix Commands</h3>";
echo "<pre>";
echo "mysql -u root -p\n";
echo "CREATE DATABASE billing;\n";
echo "CREATE DATABASE radius;\n";
echo "GRANT ALL PRIVILEGES ON billing.* TO 'billing'@'localhost' IDENTIFIED BY 'Billing123';\n";
echo "GRANT ALL PRIVILEGES ON radius.* TO 'billing'@'localhost' IDENTIFIED BY 'Billing123';\n";
echo "FLUSH PRIVILEGES;\n";
echo "EXIT;\n";
echo "\n# Then run schema:\nphp schema.php\n";
echo "</pre>";
?>

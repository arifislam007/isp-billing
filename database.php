<?php
/**
 * Database Connection Class
 */

class Database {
    private static $billingInstance = null;
    private static $radiusInstance = null;
    
    // Billing Database Connection
    public static function getBillingDB() {
        if (self::$billingInstance === null) {
            try {
                self::$billingInstance = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die('Billing Database Connection Failed: ' . $e->getMessage());
            }
        }
        return self::$billingInstance;
    }
    
    // FreeRADIUS Database Connection
    public static function getRadiusDB() {
        if (self::$radiusInstance === null) {
            try {
                self::$radiusInstance = new PDO(
                    'mysql:host=' . RADIUS_DB_HOST . ';dbname=' . RADIUS_DB_NAME . ';charset=utf8mb4',
                    RADIUS_DB_USER,
                    RADIUS_DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die('RADIUS Database Connection Failed: ' . $e->getMessage());
            }
        }
        return self::$radiusInstance;
    }
    
    // Close connections
    public static function closeConnections() {
        self::$billingInstance = null;
        self::$radiusInstance = null;
    }
}

// Helper function for database queries
function query($sql, $params = [], $db = 'billing') {
    $database = $db === 'radius' ? Database::getRadiusDB() : Database::getBillingDB();
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch($sql, $params = [], $db = 'billing') {
    return query($sql, $params, $db)->fetch();
}

function fetchAll($sql, $params = [], $db = 'billing') {
    return query($sql, $params, $db)->fetchAll();
}

function lastInsertId($db = 'billing') {
    $database = $db === 'radius' ? Database::getRadiusDB() : Database::getBillingDB();
    return $database->lastInsertId();
}

<?php
/**
 * Authentication and Session Management
 */

require_once 'config.php';
require_once 'database.php';

class Auth {
    
    public static function login($username, $password) {
        $user = fetch(
            "SELECT * FROM admin_users WHERE username = ? AND status = 'active'",
            [$username],
            'billing'
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_full_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            return true;
        }
        return false;
    }
    
    public static function logout() {
        session_destroy();
        session_start();
        return true;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public static function hasRole($role) {
        return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
    }
    
    public static function hasAnyRole($roles) {
        return isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], $roles);
    }
    
    public static function getUserId() {
        return $_SESSION['admin_id'] ?? null;
    }
    
    public static function getUsername() {
        return $_SESSION['admin_username'] ?? null;
    }
    
    public static function getFullName() {
        return $_SESSION['admin_full_name'] ?? null;
    }
    
    public static function getRole() {
        return $_SESSION['admin_role'] ?? null;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function updatePassword($userId, $newPassword) {
        $hashed = self::hashPassword($newPassword);
        return query(
            "UPDATE admin_users SET password = ? WHERE id = ?",
            [$hashed, $userId],
            'billing'
        );
    }
}

// Helper functions
function isLoggedIn() {
    return Auth::isLoggedIn();
}

function requireLogin() {
    Auth::requireLogin();
}

function getCurrentUser() {
    return [
        'id' => Auth::getUserId(),
        'username' => Auth::getUsername(),
        'full_name' => Auth::getFullName(),
        'role' => Auth::getRole()
    ];
}

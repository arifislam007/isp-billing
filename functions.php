<?php
/**
 * Helper Functions for ISP Billing System
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

/**
 * Generate unique invoice number
 */
function generateInvoiceNumber() {
    $prefix = 'INV';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $date . '-' . $random;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '৳ ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Calculate date difference in days
 */
function dateDiffInDays($date1, $date2) {
    $diff = strtotime($date2) - strtotime($date1);
    return abs(round($diff / 86400));
}

/**
 * Get billing cycle in days
 */
function getBillingCycleDays($cycle) {
    $cycles = [
        'daily' => 1,
        'weekly' => 7,
        'monthly' => 30,
        'quarterly' => 90,
        'yearly' => 365
    ];
    return $cycles[$cycle] ?? 30;
}

/**
 * Calculate package end date
 */
function calculateEndDate($startDate, $billingCycle) {
    $days = getBillingCycleDays($billingCycle);
    return date('Y-m-d', strtotime("+{$days} days", strtotime($startDate)));
}

/**
 * Convert speed from Kbps to Mbps
 */
function formatSpeed($speedKbps) {
    if ($speedKbps >= 1024) {
        return round($speedKbps / 1024, 2) . ' Mbps';
    }
    return $speedKbps . ' Kbps';
}

/**
 * Convert bandwidth from bytes to human readable
 */
function formatBandwidth($bytes) {
    if ($bytes == 0) return 'Unlimited';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    $size = $bytes;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'paid' => 'success',
        'cancelled' => 'danger',
        'overdue' => 'danger',
        'suspended' => 'warning',
        'expired' => 'secondary',
        'disconnected' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    return ucfirst($status);
}

/**
 * Calculate due days
 */
function getDueDays($dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $today->diff($due);
    
    if ($due < $today) {
        return -$diff->days;
    }
    return $diff->days;
}

/**
 * Check if invoice is overdue
 */
function isInvoiceOverdue($dueDate, $status) {
    if ($status !== 'pending') return false;
    return getDueDays($dueDate) < 0;
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    $stats = [];
    
    // Total customers
    $stats['total_customers'] = fetch("SELECT COUNT(*) as count FROM customers", [], 'billing')['count'];
    
    // Active customers
    $stats['active_customers'] = fetch("SELECT COUNT(*) as count FROM customers WHERE status = 'active'", [], 'billing')['count'];
    
    // Total packages
    $stats['total_packages'] = fetch("SELECT COUNT(*) as count FROM packages WHERE status = 'active'", [], 'billing')['count'];
    
    // Total invoices this month
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $stats['monthly_invoices'] = fetch(
        "SELECT COUNT(*) as count FROM invoices WHERE created_at BETWEEN ? AND ?",
        [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'],
        'billing'
    )['count'];
    
    // Pending invoices
    $stats['pending_invoices'] = fetch("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'", [], 'billing')['count'];
    
    // Total revenue this month
    $revenue = fetch(
        "SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p 
         INNER JOIN invoices i ON p.invoice_id = i.id 
         WHERE p.payment_date BETWEEN ? AND ?",
        [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'],
        'billing'
    );
    $stats['monthly_revenue'] = $revenue['total'];
    
    // Pending payments amount
    $pending = fetch(
        "SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'pending'",
        [],
        'billing'
    );
    $stats['pending_amount'] = $pending['total'];
    
    // NAS count
    $stats['total_nas'] = fetch("SELECT COUNT(*) as count FROM nas WHERE status = 'active'", [], 'billing')['count'];
    
    return $stats;
}

/**
 * Get customer statistics
 */
function getCustomerStats() {
    return [
        'total' => fetch("SELECT COUNT(*) as count FROM customers", [], 'billing')['count'],
        'active' => fetch("SELECT COUNT(*) as count FROM customers WHERE status = 'active'", [], 'billing')['count'],
        'inactive' => fetch("SELECT COUNT(*) as count FROM customers WHERE status = 'inactive'", [], 'billing')['count'],
        'suspended' => fetch("SELECT COUNT(*) as count FROM customers WHERE status = 'suspended'", [], 'billing')['count'],
        'disconnected' => fetch("SELECT COUNT(*) as count FROM customers WHERE status = 'disconnected'", [], 'billing')['count']
    ];
}

/**
 * Pagination helper
 */
function getPagination($totalRecords, $page = 1, $recordsPerPage = 20) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($page - 1) * $recordsPerPage;
    
    return [
        'page' => $page,
        'records_per_page' => $recordsPerPage,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'offset' => $offset,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Flash message helper
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'text' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * CSRF Token generation
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

<?php
/**
 * AJAX Handler for ISP Billing System
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Only allow logged in users
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');

$response = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'get_customer_package':
        $customer_id = intval($_GET['customer_id'] ?? 0);
        if ($customer_id > 0) {
            $package = fetch(
                "SELECT cp.package_id, p.name as package_name FROM customer_packages cp
                 JOIN packages p ON cp.package_id = p.id
                 WHERE cp.customer_id = ? AND cp.status = 'active'
                 ORDER BY cp.created_at DESC LIMIT 1",
                [$customer_id],
                'billing'
            );
            
            if ($package) {
                $response = [
                    'success' => true,
                    'package_id' => $package['package_id'],
                    'package_name' => $package['package_name']
                ];
            } else {
                $response = ['success' => true, 'package_id' => '', 'package_name' => 'No active package'];
            }
        }
        break;
        
    case 'get_invoice_details':
        $invoice_id = intval($_GET['invoice_id'] ?? 0);
        if ($invoice_id > 0) {
            $invoice = fetch(
                "SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
                 FROM invoices i
                 LEFT JOIN customers c ON i.customer_id = c.id
                 WHERE i.id = ?",
                [$invoice_id],
                'billing'
            );
            
            if ($invoice) {
                $response = [
                    'success' => true,
                    'invoice_number' => $invoice['invoice_number'],
                    'customer_name' => $invoice['customer_name'] ?? 'N/A',
                    'due_date' => formatDate($invoice['due_date']),
                    'amount' => $invoice['total_amount'],
                    'amount_formatted' => formatCurrency($invoice['total_amount'])
                ];
            }
        }
        break;
        
    case 'check_username':
        $username = sanitize($_GET['username'] ?? '');
        if (!empty($username)) {
            $exists = fetch("SELECT id FROM customers WHERE username = ?", [$username], 'billing');
            $response = [
                'success' => true,
                'available' => !$exists
            ];
        }
        break;
        
    case 'check_email':
        $email = sanitize($_GET['email'] ?? '');
        if (!empty($email)) {
            $exists = fetch("SELECT id FROM customers WHERE email = ?", [$email], 'billing');
            $response = [
                'success' => true,
                'available' => !$exists
            ];
        }
        break;
        
    case 'get_customer_info':
        $customer_id = intval($_GET['customer_id'] ?? 0);
        if ($customer_id > 0) {
            $customer = fetch(
                "SELECT * FROM customers WHERE id = ?",
                [$customer_id],
                'billing'
            );
            
            if ($customer) {
                // Get current package
                $package = fetch(
                    "SELECT p.name as package_name, cp.end_date FROM customer_packages cp
                     JOIN packages p ON cp.package_id = p.id
                     WHERE cp.customer_id = ? AND cp.status = 'active'
                     ORDER BY cp.created_at DESC LIMIT 1",
                    [$customer_id],
                    'billing'
                );
                
                $response = [
                    'success' => true,
                    'customer' => $customer,
                    'package' => $package
                ];
            }
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Unknown action'];
}

echo json_encode($response);

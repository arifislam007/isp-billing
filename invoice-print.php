<?php
/**
 * Invoice Print Page - Print-friendly version
 */

$pageTitle = 'Print Invoice';
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

requireLogin();

$invoice_id = intval($_GET['id'] ?? 0);

if ($invoice_id <= 0) {
    die('Invalid invoice ID');
}

$invoice = fetch(
    "SELECT i.*, 
     CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.username, c.email, c.phone, c.address,
     p.name as package_name, p.description as package_description
     FROM invoices i
     LEFT JOIN customers c ON i.customer_id = c.id
     LEFT JOIN packages p ON i.package_id = p.id
     WHERE i.id = ?",
    [$invoice_id],
    'billing'
);

if (!$invoice) {
    die('Invoice not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-size: 12px;
            }
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }
        body {
            background: #f5f5f5;
        }
        .invoice-box {
            background: white;
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="text-primary"><?php echo APP_NAME; ?></h2>
                    <p class="mb-0">Internet Service Provider</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h4>INVOICE</h4>
                    <p class="mb-0">Invoice #: <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></p>
                    <p class="mb-0">Date: <?php echo formatDate($invoice['created_at']); ?></p>
                    <p class="mb-0">Due Date: <?php echo formatDate($invoice['due_date']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted">Bill To:</h6>
                <strong><?php echo htmlspecialchars($invoice['customer_name'] ?? 'N/A'); ?></strong>
                <br>
                <small>Username: <?php echo htmlspecialchars($invoice['username'] ?? 'N/A'); ?></small>
                <br>
                Email: <?php echo htmlspecialchars($invoice['email']); ?>
                <br>
                Phone: <?php echo htmlspecialchars($invoice['phone']); ?>
                <?php if (!empty($invoice['address'])): ?>
                <br>
                Address: <?php echo htmlspecialchars($invoice['address']); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="alert alert-info mb-4">
            <strong>Billing Period:</strong> 
            <?php echo formatDate($invoice['billing_period_start']); ?> to 
            <?php echo formatDate($invoice['billing_period_end']); ?>
        </div>
        
        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($invoice['package_name'] ?? 'N/A'); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($invoice['package_description'] ?? ''); ?></small>
                    </td>
                    <td class="text-end"><?php echo formatCurrency($invoice['amount']); ?></td>
                </tr>
                <?php if ($invoice['tax_amount'] > 0): ?>
                <tr>
                    <td>Tax</td>
                    <td class="text-end"><?php echo formatCurrency($invoice['tax_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="table-active">
                    <td><strong>Total Amount</strong></td>
                    <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($invoice['notes'])): ?>
        <div class="mb-4">
            <h6>Notes:</h6>
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="row mt-5">
            <div class="col-md-6">
                <p class="text-muted mb-4">Customer Signature</p>
                <hr style="width: 200px;">
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-4">Authorized Signature</p>
                <hr style="width: 200px; margin-left: auto;">
            </div>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Print Invoice
            </button>
            <button onclick="window.close()" class="btn btn-secondary ms-2">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>
        
        <div class="text-center mt-3 text-muted">
            <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Thank you for your business!</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

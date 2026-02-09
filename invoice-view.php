<?php
/**
 * Invoice View Page
 */

$pageTitle = 'Invoice Details - ' . APP_NAME;
require_once 'header.php';

$invoice_id = intval($_GET['id'] ?? 0);

if ($invoice_id <= 0) {
    setFlashMessage('error', 'Invalid invoice ID');
    redirect('invoices.php');
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
    setFlashMessage('error', 'Invoice not found');
    redirect('invoices.php');
}

// Get payment history for this invoice
$payments = fetchAll(
    "SELECT p.*, a.full_name as received_by_name FROM payments p
     LEFT JOIN admin_users a ON p.received_by = a.id
     WHERE p.invoice_id = ?
     ORDER BY p.payment_date DESC",
    [$invoice_id],
    'billing'
);

// Handle quick payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_pay') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        
        // Insert payment
        query(
            "INSERT INTO payments (invoice_id, customer_id, amount, payment_method, transaction_id, received_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$invoice_id, $invoice['customer_id'], $invoice['total_amount'], $payment_method, $transaction_id, Auth::getUserId()],
            'billing'
        );
        
        // Update invoice status
        query(
            "UPDATE invoices SET status = 'paid', paid_date = CURDATE() WHERE id = ?",
            [$invoice_id],
            'billing'
        );
        
        setFlashMessage('success', 'Payment recorded successfully!');
        redirect('invoice-view.php?id=' . $invoice_id);
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Invoice Details</h5>
                <div>
                    <a href="invoice-print.php?id=<?php echo $invoice_id; ?>" class="btn btn-sm btn-light" target="_blank">
                        <i class="fas fa-print me-1"></i>Print
                    </a>
                    <a href="invoices.php" class="btn btn-sm btn-outline-light ms-1">
                        <i class="fas fa-list me-1"></i>All Invoices
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Invoice Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h4>
                        <span class="badge bg-<?php echo getStatusBadgeClass($invoice['status']); ?> fs-6">
                            <?php echo getStatusLabel($invoice['status']); ?>
                        </span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">Issue Date: <?php echo formatDate($invoice['created_at']); ?></p>
                        <p class="mb-0">Due Date: <?php echo formatDate($invoice['due_date']); ?></p>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Bill To:</h6>
                        <strong><?php echo htmlspecialchars($invoice['customer_name'] ?? 'N/A'); ?></strong>
                        <br>
                        <small>@<?php echo htmlspecialchars($invoice['username'] ?? 'N/A'); ?></small>
                        <br>
                        <?php echo htmlspecialchars($invoice['email']); ?>
                        <br>
                        <?php echo htmlspecialchars($invoice['phone']); ?>
                        <?php if (!empty($invoice['address'])): ?>
                        <br>
                        <?php echo htmlspecialchars($invoice['address']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Billing Period -->
                <div class="alert alert-info mb-4">
                    <strong>Billing Period:</strong> 
                    <?php echo formatDate($invoice['billing_period_start']); ?> to 
                    <?php echo formatDate($invoice['billing_period_end']); ?>
                </div>
                
                <!-- Invoice Items -->
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
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Notes -->
                <?php if (!empty($invoice['notes'])): ?>
                <div class="mb-4">
                    <h6>Notes:</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment History</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th>Received By</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['received_by_name'] ?? 'System'); ?></td>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Payment Form -->
                <?php if ($invoice['status'] === 'pending'): ?>
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-credit-card me-2"></i>Record Payment</h6>
                        <form method="POST" action="" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="quick_pay">
                            
                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="bkash">Bkash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="card">Card</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transaction ID (Optional)</label>
                                <input type="text" class="form-control" name="transaction_id" 
                                       placeholder="Transaction ID">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check me-1"></i>Mark as Paid (<?php echo formatCurrency($invoice['total_amount']); ?>)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

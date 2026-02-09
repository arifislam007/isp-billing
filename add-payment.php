<?php
/**
 * Record Payment Page
 */

$pageTitle = 'Record Payment - ' . APP_NAME;
require_once 'header.php';

// Get pending invoices for dropdown
$pendingInvoices = fetchAll(
    "SELECT i.id, i.invoice_number, i.total_amount, i.due_date,
     CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.username,
     p.name as package_name
     FROM invoices i
     LEFT JOIN customers c ON i.customer_id = c.id
     LEFT JOIN packages p ON i.package_id = p.id
     WHERE i.status = 'pending'
     ORDER BY i.created_at DESC",
    [],
    'billing'
);

$error = '';
$invoice_id = intval($_GET['invoice_id'] ?? 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $invoice_id = intval($_POST['invoice_id'] ?? 0);
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        
        if ($invoice_id <= 0) {
            $error = 'Please select an invoice';
        } else {
            // Get invoice details
            $invoice = fetch("SELECT * FROM invoices WHERE id = ?", [$invoice_id], 'billing');
            
            if (!$invoice) {
                $error = 'Invoice not found';
            } elseif ($invoice['status'] !== 'pending') {
                $error = 'Invoice is not pending payment';
            } else {
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
                redirect('payments.php');
            }
        }
    }
}

// Pre-select invoice if provided
$selectedInvoice = null;
if ($invoice_id > 0) {
    $selectedInvoice = fetch(
        "SELECT i.id, i.invoice_number, i.total_amount, i.due_date,
         CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.username
         FROM invoices i
         LEFT JOIN customers c ON i.customer_id = c.id
         WHERE i.id = ? AND i.status = 'pending'",
        [$invoice_id],
        'billing'
    );
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Record Payment</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="invoice_id" class="form-label">Select Invoice *</label>
                        <select class="form-select" id="invoice_id" name="invoice_id" required>
                            <option value="">-- Select Pending Invoice --</option>
                            <?php foreach ($pendingInvoices as $inv): ?>
                            <option value="<?php echo $inv['id']; ?>" 
                                    data-amount="<?php echo $inv['total_amount']; ?>"
                                    <?php echo $selectedInvoice && $selectedInvoice['id'] == $inv['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inv['invoice_number']); ?> - 
                                <?php echo htmlspecialchars($inv['customer_name']); ?> (@<?php echo htmlspecialchars($inv['username']); ?>) - 
                                <?php echo formatCurrency($inv['total_amount']); ?> (Due: <?php echo formatDate($inv['due_date']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Invoice Details -->
                    <div id="invoiceDetails" class="card mb-3 <?php echo !$selectedInvoice ? 'd-none' : ''; ?>">
                        <div class="card-body">
                            <h6>Invoice Details:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Customer:</strong> <span id="custName">-</span></p>
                                    <p class="mb-1"><strong>Due Date:</strong> <span id="dueDate">-</span></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="mb-1"><strong>Amount Due:</strong></p>
                                    <h3 class="text-success" id="amountDue">৳ 0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="bkash">Bkash</option>
                                <option value="nagad">Nagad</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_id" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" 
                                   placeholder="Enter transaction ID (if applicable)">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Record Payment
                        </button>
                        <a href="payments.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('invoice_id').addEventListener('change', function() {
    const select = this;
    const option = select.options[select.selectedIndex];
    const detailsDiv = document.getElementById('invoiceDetails');
    
    if (select.value && option) {
        // Get invoice ID and fetch details via AJAX
        const invoiceId = select.value;
        fetch('ajax.php?action=get_invoice_details&invoice_id=' + invoiceId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('custName').textContent = data.customer_name;
                    document.getElementById('dueDate').textContent = data.due_date;
                    document.getElementById('amountDue').textContent = data.amount_formatted;
                    detailsDiv.classList.remove('d-none');
                } else {
                    detailsDiv.classList.add('d-none');
                }
            });
    } else {
        detailsDiv.classList.add('d-none');
    }
});

// Trigger on page load if invoice is pre-selected
<?php if ($selectedInvoice): ?>
document.getElementById('invoice_id').dispatchEvent(new Event('change'));
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>

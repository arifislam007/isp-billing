<?php
/**
 * Create Invoice Page
 */

$pageTitle = 'Create Invoice - ' . APP_NAME;
require_once 'header.php';

// Get customers for dropdown
$customers = fetchAll(
    "SELECT id, username, first_name, last_name, email FROM customers WHERE status != 'disconnected' ORDER BY first_name",
    [],
    'billing'
);

// Get packages for dropdown
$packages = fetchAll(
    "SELECT id, name, price FROM packages WHERE status = 'active' ORDER BY price",
    [],
    'billing'
);

$error = '';
$customer_id = intval($_GET['customer_id'] ?? 0);

// Pre-select customer if provided
$selectedCustomer = null;
if ($customer_id > 0) {
    $selectedCustomer = fetch(
        "SELECT id, username, first_name, last_name FROM customers WHERE id = ?",
        [$customer_id],
        'billing'
    );
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $package_id = intval($_POST['package_id'] ?? 0);
        $billing_period_start = sanitize($_POST['billing_period_start'] ?? '');
        $billing_period_end = sanitize($_POST['billing_period_end'] ?? '');
        $due_date = sanitize($_POST['due_date'] ?? '');
        $tax_amount = floatval($_POST['tax_amount'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');
        
        // Validation
        if ($customer_id <= 0) {
            $error = 'Please select a customer';
        } elseif ($package_id <= 0) {
            $error = 'Please select a package';
        } elseif (empty($billing_period_start) || empty($billing_period_end) || empty($due_date)) {
            $error = 'Please fill in all date fields';
        } else {
            // Get package details
            $package = fetch("SELECT * FROM packages WHERE id = ?", [$package_id], 'billing');
            
            if (!$package) {
                $error = 'Package not found';
            } else {
                $total_amount = $package['price'] + $tax_amount;
                $invoice_number = generateInvoiceNumber();
                
                // Insert invoice
                query(
                    "INSERT INTO invoices (invoice_number, customer_id, package_id, billing_period_start, billing_period_end, 
                     amount, tax_amount, total_amount, status, due_date, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)",
                    [$invoice_number, $customer_id, $package_id, $billing_period_start, $billing_period_end, 
                     $package['price'], $tax_amount, $total_amount, $due_date, $notes],
                    'billing'
                );
                
                $invoiceId = lastInsertId('billing');
                
                setFlashMessage('success', 'Invoice created successfully! Invoice #: ' . $invoice_number);
                redirect('invoices.php');
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Create Invoice</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required onchange="updateCustomerPackage(this.value)">
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>" 
                                    <?php echo $selectedCustomer && $selectedCustomer['id'] == $cust['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name'] . ' (' . $cust['username'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="package_id" class="form-label">Package *</label>
                        <select class="form-select" id="package_id" name="package_id" required onchange="updateAmount(this.value)">
                            <option value="">-- Select Package --</option>
                            <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg['id']; ?>" data-price="<?php echo $pkg['price']; ?>">
                                <?php echo htmlspecialchars($pkg['name']); ?> - <?php echo formatCurrency($pkg['price']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="billing_period_start" class="form-label">Billing Period Start *</label>
                            <input type="date" class="form-control" id="billing_period_start" name="billing_period_start" 
                                   value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="billing_period_end" class="form-label">Billing Period End *</label>
                            <input type="date" class="form-control" id="billing_period_end" name="billing_period_end" 
                                   value="<?php echo date('Y-m-t'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date *</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Package Amount (৳)</label>
                            <input type="number" class="form-control" id="amount" value="0.00" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="tax_amount" class="form-label">Tax Amount (৳)</label>
                            <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                   value="0.00" min="0" step="0.01" onchange="calculateTotal()">
                        </div>
                        <div class="col-md-4">
                            <label for="total_amount" class="form-label">Total Amount (৳)</label>
                            <input type="number" class="form-control" id="total_amount" value="0.00" readonly style="font-weight: bold;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes for this invoice"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Create Invoice
                        </button>
                        <a href="invoices.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateCustomerPackage(customerId) {
    if (customerId) {
        fetch('ajax.php?action=get_customer_package&customer_id=' + customerId)
            .then(response => response.json())
            .then(data => {
                if (data.package_id) {
                    const select = document.getElementById('package_id');
                    select.value = data.package_id;
                    updateAmount(data.package_id);
                }
            });
    }
}

function updateAmount(packageId) {
    const select = document.getElementById('package_id');
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    
    document.getElementById('amount').value = parseFloat(price).toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    const total = amount + tax;
    
    document.getElementById('total_amount').value = total.toFixed(2);
}
</script>

<?php require_once 'footer.php'; ?>

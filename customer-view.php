<?php
/**
 * Customer View Page
 */

$pageTitle = 'Customer Details - ' . APP_NAME;
require_once 'header.php';

$customer_id = intval($_GET['id'] ?? 0);

if ($customer_id <= 0) {
    setFlashMessage('error', 'Invalid customer ID');
    redirect('customers.php');
}

$customer = fetch(
    "SELECT * FROM customers WHERE id = ?",
    [$customer_id],
    'billing'
);

if (!$customer) {
    setFlashMessage('error', 'Customer not found');
    redirect('customers.php');
}

// Get customer's current package
$currentPackage = fetch(
    "SELECT cp.*, p.name as package_name, p.download_speed, p.upload_speed, p.price, p.billing_cycle
     FROM customer_packages cp
     JOIN packages p ON cp.package_id = p.id
     WHERE cp.customer_id = ? AND cp.status = 'active'
     ORDER BY cp.created_at DESC LIMIT 1",
    [$customer_id],
    'billing'
);

// Get customer's invoices
$invoices = fetchAll(
    "SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10",
    [$customer_id],
    'billing'
);

// Get customer's payments
$payments = fetchAll(
    "SELECT p.*, i.invoice_number FROM payments p
     LEFT JOIN invoices i ON p.invoice_id = i.id
     WHERE p.customer_id = ?
     ORDER BY p.payment_date DESC LIMIT 10",
    [$customer_id],
    'billing'
);

// Get billing history
$billingHistory = fetchAll(
    "SELECT cp.*, p.name as package_name, p.price
     FROM customer_packages cp
     JOIN packages p ON cp.package_id = p.id
     WHERE cp.customer_id = ?
     ORDER BY cp.created_at DESC",
    [$customer_id],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <!-- Customer Info Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="customer-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                </div>
                <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                <p class="text-muted mb-1">@<?php echo htmlspecialchars($customer['username']); ?></p>
                <span class="badge bg-<?php echo getStatusBadgeClass($customer['status']); ?> mb-3">
                    <?php echo getStatusLabel($customer['status']); ?>
                </span>
                
                <div class="text-start mt-4">
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2 text-muted"></i>
                        <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </a>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-phone me-2 text-muted"></i>
                        <?php echo htmlspecialchars($customer['phone']); ?>
                    </p>
                    <?php if (!empty($customer['address'])): ?>
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                        <?php echo htmlspecialchars($customer['address']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($customer['nid_number'])): ?>
                    <p class="mb-2">
                        <i class="fas fa-id-card me-2 text-muted"></i>
                        NID: <?php echo htmlspecialchars($customer['nid_number']); ?>
                    </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2 text-muted"></i>
                        Since: <?php echo formatDate($customer['created_at']); ?>
                    </p>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-grid gap-2">
                    <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Edit Customer
                    </a>
                    <a href="assign-package.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success">
                        <i class="fas fa-box me-1"></i>Assign Package
                    </a>
                    <a href="add-invoice.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-file-invoice me-1"></i>Generate Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Current Package -->
        <?php if ($currentPackage): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Current Package</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-success"><?php echo htmlspecialchars($currentPackage['package_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($currentPackage['billing_cycle']); ?>ly billing</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h3 class="text-primary"><?php echo formatCurrency($currentPackage['price']); ?></h3>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-4">
                        <i class="fas fa-arrow-down text-primary fa-2x"></i>
                        <p class="mb-0 mt-2"><?php echo formatSpeed($currentPackage['download_speed']); ?></p>
                        <small class="text-muted">Download</small>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-arrow-up text-success fa-2x"></i>
                        <p class="mb-0 mt-2"><?php echo formatSpeed($currentPackage['upload_speed']); ?></p>
                        <small class="text-muted">Upload</small>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-calendar-alt text-warning fa-2x"></i>
                        <p class="mb-0 mt-2"><?php echo formatDate($currentPackage['end_date']); ?></p>
                        <small class="text-muted">Expires</small>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h5>No Active Package</h5>
                <p class="text-muted">This customer doesn't have an active package.</p>
                <a href="assign-package.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Assign Package
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Billing History -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Billing History</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($billingHistory)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Price</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($billingHistory as $history): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($history['package_name']); ?></td>
                                <td><?php echo formatCurrency($history['price']); ?></td>
                                <td><?php echo formatDate($history['start_date']); ?></td>
                                <td><?php echo $history['end_date'] ? formatDate($history['end_date']) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($history['status']); ?>">
                                        <?php echo getStatusLabel($history['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted py-3 mb-0">No billing history found</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Invoices -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Invoices</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($invoices)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td><?php echo formatDate($invoice['due_date']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($invoice['status']); ?>">
                                        <?php echo getStatusLabel($invoice['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoice-view.php?id=<?php echo $invoice['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted py-3 mb-0">No invoices found</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Recent Payments</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($payments)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['invoice_number'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></td>
                                <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted py-3 mb-0">No payments found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

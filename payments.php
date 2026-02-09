<?php
/**
 * Payments List Page
 */

$pageTitle = 'Payments - ' . APP_NAME;
require_once 'header.php';

// Handle payment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        
        if ($payment_id > 0) {
            // Get invoice_id before deleting
            $payment = fetch("SELECT invoice_id FROM payments WHERE id = ?", [$payment_id], 'billing');
            
            if ($payment) {
                query("DELETE FROM payments WHERE id = ?", [$payment_id], 'billing');
                
                // Update invoice status back to pending
                query(
                    "UPDATE invoices SET status = 'pending', paid_date = NULL WHERE id = ?",
                    [$payment['invoice_id']],
                    'billing'
                );
                
                setFlashMessage('success', 'Payment deleted and invoice marked as pending!');
            }
        }
    }
    redirect('payments.php');
}

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$method_filter = sanitize($_GET['method'] ?? '');
$page = intval($_GET['page'] ?? 1);
$recordsPerPage = 25;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.transaction_id LIKE ? OR c.username LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR i.invoice_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($method_filter)) {
    $where[] = "p.payment_method = ?";
    $params[] = $method_filter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalRecords = fetch(
    "SELECT COUNT(*) as count FROM payments p 
     LEFT JOIN customers c ON p.customer_id = c.id 
     LEFT JOIN invoices i ON p.invoice_id = i.id 
     {$whereClause}",
    $params,
    'billing'
)['count'];

// Get payments with pagination
$payments = fetchAll(
    "SELECT p.*, i.invoice_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
     a.full_name as received_by_name
     FROM payments p
     LEFT JOIN invoices i ON p.invoice_id = i.id
     LEFT JOIN customers c ON p.customer_id = c.id
     LEFT JOIN admin_users a ON p.received_by = a.id
     {$whereClause}
     ORDER BY p.payment_date DESC LIMIT ? OFFSET ?",
    array_merge($params, [$recordsPerPage, ($page - 1) * $recordsPerPage]),
    'billing'
);

$pagination = getPagination($totalRecords, $page, $recordsPerPage);

// Get summary statistics
$todayPayments = fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()",
    [],
    'billing'
);
$monthPayments = fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())",
    [],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-12">
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Today's Collection</h6>
                        <h3><?php echo formatCurrency($todayPayments['total']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">This Month</h6>
                        <h3><?php echo formatCurrency($monthPayments['total']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Transactions</h6>
                        <h3><?php echo number_format($totalRecords); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Management</h5>
                <a href="add-payment.php" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Record Payment
                </a>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search payments..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="method">
                                <option value="">All Methods</option>
                                <option value="cash" <?php echo $method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="bkash" <?php echo $method_filter === 'bkash' ? 'selected' : ''; ?>>Bkash</option>
                                <option value="nagad" <?php echo $method_filter === 'nagad' ? 'selected' : ''; ?>>Nagad</option>
                                <option value="card" <?php echo $method_filter === 'card' ? 'selected' : ''; ?>>Card</option>
                                <option value="other" <?php echo $method_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="payments.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Payments Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th>Received By</th>
                                <th class="action-buttons">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr data-id="<?php echo $payment['id']; ?>">
                                <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                                <td>
                                    <a href="invoice-view.php?id=<?php echo $payment['invoice_id']; ?>">
                                        <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $methodIcons = [
                                        'cash' => 'fas fa-money-bill',
                                        'bank_transfer' => 'fas fa-university',
                                        'bkash' => 'fas fa-mobile-alt',
                                        'nagad' => 'fas fa-wallet',
                                        'card' => 'fas fa-credit-card',
                                        'other' => 'fas fa-ellipsis-h'
                                    ];
                                    ?>
                                    <i class="<?php echo $methodIcons[$payment['payment_method']] ?? 'fas fa-money-bill'; ?> me-1"></i>
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_method']))); ?>
                                </td>
                                <td><?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($payment['received_by_name'] ?? 'System'); ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="App.deleteItem('payments.php', <?php echo $payment['id']; ?>, 'Payment deleted!')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <p class="mb-0">No payments found</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&method=<?php echo urlencode($method_filter); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&method=<?php echo urlencode($method_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&method=<?php echo urlencode($method_filter); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

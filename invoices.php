<?php
/**
 * Invoices List Page
 */

$pageTitle = 'Invoices - ' . APP_NAME;
require_once 'header.php';

// Handle invoice actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $action = $_POST['action'];
        $invoice_id = intval($_POST['invoice_id'] ?? 0);
        
        if ($action === 'mark_paid' && $invoice_id > 0) {
            query(
                "UPDATE invoices SET status = 'paid', paid_date = CURDATE() WHERE id = ?",
                [$invoice_id],
                'billing'
            );
            setFlashMessage('success', 'Invoice marked as paid!');
        } elseif ($action === 'cancel' && $invoice_id > 0) {
            query(
                "UPDATE invoices SET status = 'cancelled' WHERE id = ?",
                [$invoice_id],
                'billing'
            );
            setFlashMessage('success', 'Invoice cancelled!');
        } elseif ($action === 'delete' && $invoice_id > 0) {
            query("DELETE FROM invoices WHERE id = ?", [$invoice_id], 'billing');
            setFlashMessage('success', 'Invoice deleted!');
        }
    }
    redirect('invoices.php');
}

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$page = intval($_GET['page'] ?? 1);
$recordsPerPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(i.invoice_number LIKE ? OR c.username LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($status_filter)) {
    $where[] = "i.status = ?";
    $params[] = $status_filter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalRecords = fetch(
    "SELECT COUNT(*) as count FROM invoices i 
     LEFT JOIN customers c ON i.customer_id = c.id {$whereClause}",
    $params,
    'billing'
)['count'];

// Get invoices with pagination
$invoices = fetchAll(
    "SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.username,
     p.name as package_name
     FROM invoices i 
     LEFT JOIN customers c ON i.customer_id = c.id
     LEFT JOIN packages p ON i.package_id = p.id
     {$whereClause} 
     ORDER BY i.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$recordsPerPage, ($page - 1) * $recordsPerPage]),
    'billing'
);

$pagination = getPagination($totalRecords, $page, $recordsPerPage);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Invoice Management</h5>
                <a href="add-invoice.php" class="btn btn-sm btn-dark">
                    <i class="fas fa-plus me-1"></i>Create Invoice
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
                                       placeholder="Search invoices..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="invoices.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Invoices Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="invoicesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Billing Period</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="action-buttons">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <?php $isOverdue = isInvoiceOverdue($invoice['due_date'], $invoice['status']); ?>
                            <tr data-id="<?php echo $invoice['id']; ?>" class="<?php echo $isOverdue ? 'table-warning' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($invoice['customer_name'] ?? 'N/A'); ?>
                                    <br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($invoice['username'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($invoice['package_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td>
                                    <?php echo formatDate($invoice['billing_period_start']); ?> - 
                                    <?php echo formatDate($invoice['billing_period_end']); ?>
                                </td>
                                <td>
                                    <?php echo formatDate($invoice['due_date']); ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($invoice['status']); ?>">
                                        <?php echo getStatusLabel($invoice['status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <div class="btn-group btn-group-sm">
                                        <a href="invoice-view.php?id=<?php echo $invoice['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="invoice-print.php?id=<?php echo $invoice['id']; ?>" 
                                           class="btn btn-outline-secondary" title="Print" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if ($invoice['status'] === 'pending'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Mark as Paid">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($invoice['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <button type="submit" class="btn btn-outline-warning" title="Cancel">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-file-invoice"></i>
                                        <p class="mb-0">No invoices found</p>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
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

<?php
/**
 * Customers List Page
 */

$pageTitle = 'Customers - ' . APP_NAME;
require_once 'header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $action = $_POST['action'];
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        if ($action === 'update_status' && $customer_id > 0) {
            $status = sanitize($_POST['status'] ?? '');
            query(
                "UPDATE customers SET status = ? WHERE id = ?",
                [$status, $customer_id],
                'billing'
            );
            setFlashMessage('success', 'Customer status updated successfully!');
        } elseif ($action === 'delete' && $customer_id > 0) {
            // Get username to remove from RADIUS
            $customer = fetch("SELECT username FROM customers WHERE id = ?", [$customer_id], 'billing');
            if ($customer) {
                query("DELETE FROM radcheck WHERE username = ?", [$customer['username']], 'radius');
            }
            query("DELETE FROM customers WHERE id = ?", [$customer_id], 'billing');
            setFlashMessage('success', 'Customer deleted successfully!');
        }
    }
    redirect('customers.php');
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
    $where[] = "(username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalRecords = fetch(
    "SELECT COUNT(*) as count FROM customers {$whereClause}",
    $params,
    'billing'
)['count'];

// Get customers with pagination
$customers = fetchAll(
    "SELECT * FROM customers {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$recordsPerPage, ($page - 1) * $recordsPerPage]),
    'billing'
);

$pagination = getPagination($totalRecords, $page, $recordsPerPage);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Customer Management</h5>
                <a href="add-customer.php" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Add New Customer
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
                                       placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="disconnected" <?php echo $status_filter === 'disconnected' ? 'selected' : ''; ?>>Disconnected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="customers.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Customers Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="customersTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="action-buttons">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <?php
                            $package = fetch(
                                "SELECT p.name FROM customer_packages cp 
                                 JOIN packages p ON cp.package_id = p.id 
                                 WHERE cp.customer_id = ? AND cp.status = 'active' 
                                 ORDER BY cp.created_at DESC LIMIT 1",
                                [$customer['id']],
                                'billing'
                            );
                            ?>
                            <tr data-id="<?php echo $customer['id']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td>
                                    <?php if ($package): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($package['name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">No package</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($customer['status']); ?>">
                                        <?php echo getStatusLabel($customer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($customer['created_at']); ?></td>
                                <td class="action-buttons">
                                    <div class="btn-group btn-group-sm">
                                        <a href="customer-view.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="App.deleteItem('customers.php', <?php echo $customer['id']; ?>, 'Customer deleted successfully!')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Status Dropdown -->
                                    <div class="dropdown d-inline-block ms-1">
                                        <button class="btn btn-outline-warning btn-sm dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" 
                                                   onclick="updateStatus(<?php echo $customer['id']; ?>, 'active'); return false;">
                                                   <i class="fas fa-check text-success me-2"></i>Active
                                                   </a></li>
                                            <li><a class="dropdown-item" href="#" 
                                                   onclick="updateStatus(<?php echo $customer['id']; ?>, 'inactive'); return false;">
                                                   <i class="fas fa-pause text-secondary me-2"></i>Inactive
                                                   </a></li>
                                            <li><a class="dropdown-item" href="#" 
                                                   onclick="updateStatus(<?php echo $customer['id']; ?>, 'suspended'); return false;">
                                                   <i class="fas fa-exclamation-triangle text-warning me-2"></i>Suspended
                                                   </a></li>
                                            <li><a class="dropdown-item" href="#" 
                                                   onclick="updateStatus(<?php echo $customer['id']; ?>, 'disconnected'); return false;">
                                                   <i class="fas fa-times text-danger me-2"></i>Disconnected
                                                   </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <p class="mb-0">No customers found</p>
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

<!-- Hidden form for status update -->
<form id="statusForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="customer_id" id="statusCustomerId">
    <input type="hidden" name="status" id="statusValue">
</form>

<script>
function updateStatus(customerId, status) {
    document.getElementById('statusCustomerId').value = customerId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once 'footer.php'; ?>

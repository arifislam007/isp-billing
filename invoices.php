<?php
/**
 * Invoices Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Invoices - ' . APP_NAME;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle invoice status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $action = $_POST['action'];
            $invoice_id = intval($_POST['invoice_id'] ?? 0);
            
            if ($action === 'mark_paid' && $invoice_id > 0) {
                $stmt = $db->prepare("UPDATE invoices SET status = 'paid', paid_date = CURDATE() WHERE id = ?");
                $stmt->execute([$invoice_id]);
            } elseif ($action === 'cancel' && $invoice_id > 0) {
                $stmt = $db->prepare("UPDATE invoices SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$invoice_id]);
            }
        }
        header('Location: invoices.php');
        exit;
    }
    
    $stmt = $db->query("
        SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.username, p.name as package_name
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN packages p ON i.package_id = p.id
        ORDER BY i.created_at DESC LIMIT 50
    ");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    $invoices = [];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

function formatCurrency($amount) { return '৳ ' . number_format($amount, 2); }
function formatDate($date) { return date('Y-m-d', strtotime($date)); }
function getStatusBadgeClass($status) { $classes = ['active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger', 'overdue' => 'danger', 'suspended' => 'warning']; return $classes[$status] ?? 'secondary'; }
function getStatusLabel($status) { return ucfirst($status); }
function isOverdue($dueDate, $status) { return $status === 'pending' && strtotime($dueDate) < time(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; min-height: 100vh; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-network-wired me-2"></i><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users me-1"></i> Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="packages.php"><i class="fas fa-box me-1"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link active" href="invoices.php"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>
                    <li class="nav-item"><a class="nav-link" href="payments.php"><i class="fas fa-money-bill-wave me-1"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="nas.php"><i class="fas fa-server me-1"></i> NAS</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['full_name']); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="change-password.php">Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-file-invoice me-2"></i>Invoice Management</h2>
            <a href="add-invoice.php" class="btn btn-warning"><i class="fas fa-plus me-1"></i> Create Invoice</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <?php $overdue = isOverdue($invoice['due_date'], $invoice['status']); ?>
                            <tr class="<?php echo $overdue ? 'table-warning' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($invoice['customer_name'] ?? 'N/A'); ?><br><small class="text-muted">@<?php echo htmlspecialchars($invoice['username'] ?? 'N/A'); ?></small></td>
                                <td><?php echo htmlspecialchars($invoice['package_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td><?php echo formatDate($invoice['due_date']); ?><?php if ($overdue): ?><span class="badge bg-danger ms-1">Overdue</span><?php endif; ?></td>
                                <td><span class="badge bg-<?php echo getStatusBadgeClass($invoice['status']); ?>"><?php echo getStatusLabel($invoice['status']); ?></span></td>
                                <td>
                                    <a href="invoice-view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <?php if ($invoice['status'] === 'pending'): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($invoices)): ?>
                            <tr><td colspan="7" class="text-center py-4">No invoices found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

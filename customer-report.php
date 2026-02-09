<?php
/**
 * Customer Report Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Customer Report - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get status filter
    $status = $_GET['status'] ?? 'all';
    
    // Build query based on status filter
    $where = '';
    $params = [];
    if ($status !== 'all') {
        $where = 'WHERE c.status = ?';
        $params = [$status];
    }
    
    // Get customers with stats
    $stmt = $db->prepare("
        SELECT c.*, p.name as package_name, p.price as package_price,
               (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = c.id AND status = 'completed') as total_paid,
               (SELECT COUNT(*) FROM invoices WHERE customer_id = c.id) as total_invoices
        FROM customers c
        LEFT JOIN packages p ON c.package_id = p.id
        {$where}
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $total_customers = count($customers);
    $active_customers = count(array_filter($customers, fn($c) => $c['status'] === 'active'));
    $total_revenue = array_sum(array_column($customers, 'total_paid'));
    
    // Export to CSV
    $export = $_GET['export'] ?? '';
    if ($export === 'csv' && !empty($customers)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customer-report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Username', 'Name', 'Phone', 'Package', 'Status', 'Total Paid', 'Invoices', 'Joined']);
        foreach ($customers as $c) {
            fputcsv($output, [
                $c['id'],
                $c['username'],
                $c['first_name'] . ' ' . $c['last_name'],
                $c['phone'],
                $c['package_name'] ?: 'No Package',
                $c['status'],
                $c['total_paid'],
                $c['total_invoices'],
                date('Y-m-d', strtotime($c['created_at']))
            ]);
        }
        fclose($output);
        exit;
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $customers = [];
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];
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
                    <li class="nav-item"><a class="nav-link" href="invoices.php"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>
                    <li class="nav-item"><a class="nav-link" href="payments.php"><i class="fas fa-money-bill-wave me-1"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="nas.php"><i class="fas fa-server me-1"></i> NAS</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Customer Report</h4>
            <a href="reports.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Reports</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="disconnected" <?php echo $status === 'disconnected' ? 'selected' : ''; ?>>Disconnected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filter</button>
                    </div>
                    <div class="col-md-3">
                        <a href="?status=<?php echo $status; ?>&export=csv" class="btn btn-success w-100"><i class="fas fa-file-export me-2"></i>Export CSV</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Total Customers</h5>
                        <h3 class="mb-0"><?php echo number_format($total_customers); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Active Customers</h5>
                        <h3 class="mb-0"><?php echo number_format($active_customers); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Total Revenue</h5>
                        <h3 class="mb-0">৳ <?php echo number_format($total_revenue, 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customers Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Customer Details</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Invoices</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <a href="customer-view.php?id=<?php echo $c['id']; ?>">
                                        <strong><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></strong>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($c['username']); ?></small>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($c['phone'] ?: '-'); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($c['email'] ?: ''); ?></small>
                                </td>
                                <td>
                                    <?php if ($c['package_name']): ?>
                                    <?php echo htmlspecialchars($c['package_name']); ?>
                                    <br><small class="text-success">৳ <?php echo number_format($c['package_price'], 2); ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">No Package</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $c['status'] === 'active' ? 'success' : 
                                            ($c['status'] === 'pending' ? 'warning text-dark' : 
                                            ($c['status'] === 'suspended' ? 'danger' : 'secondary')); 
                                    ?>"><?php echo ucfirst($c['status']); ?></span>
                                </td>
                                <td class="text-end fw-bold text-success">৳ <?php echo number_format($c['total_paid'], 2); ?></td>
                                <td class="text-end"><?php echo $c['total_invoices']; ?></td>
                                <td><?php echo date('d M Y', strtotime($c['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($customers)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No customers found</td></tr>
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

<?php
/**
 * Reports Dashboard Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Reports - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get summary statistics
    $total_customers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $active_customers = $db->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn();
    $total_packages = $db->query("SELECT COUNT(*) FROM packages WHERE status = 'active'")->fetchColumn();
    
    $total_revenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
    $monthly_revenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetchColumn();
    
    $pending_invoices = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'pending'")->fetchColumn();
    $pending_amount = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status = 'pending'")->fetchColumn();
    
    $total_nas = $db->query("SELECT COUNT(*) FROM nas WHERE status = 'active'")->fetchColumn();
    
    // Monthly revenue for chart
    $monthly_data = $db->query("
        SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total
        FROM payments 
        WHERE status = 'completed' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top customers
    $top_customers = $db->query("
        SELECT c.id, c.first_name, c.last_name, c.username, SUM(p.amount) as total_paid
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id AND p.status = 'completed'
        GROUP BY c.id
        ORDER BY total_paid DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
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
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
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
        <h4 class="mb-4">Reports & Analytics</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">Total Customers</p>
                                <h3 class="mb-0"><?php echo number_format($total_customers); ?></h3>
                                <small><?php echo number_format($active_customers); ?> active</small>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">Total Revenue</p>
                                <h3 class="mb-0">৳ <?php echo number_format($total_revenue, 0); ?></h3>
                                <small>This month: ৳ <?php echo number_format($monthly_revenue, 0); ?></small>
                            </div>
                            <i class="fas fa-money-bill fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">Pending Invoices</p>
                                <h3 class="mb-0"><?php echo number_format($pending_invoices); ?></h3>
                                <small>৳ <?php echo number_format($pending_amount, 0); ?> due</small>
                            </div>
                            <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">NAS Devices</p>
                                <h3 class="mb-0"><?php echo number_format($total_nas); ?></h3>
                                <small><?php echo number_format($total_packages); ?> packages</small>
                            </div>
                            <i class="fas fa-server fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Quick Links -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-link me-2"></i>Quick Reports</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="revenue-report.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-line me-2"></i> Revenue Report
                            </a>
                            <a href="customer-report.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users me-2"></i> Customer Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Paying Customers</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_customers as $tc): ?>
                                    <tr>
                                        <td>
                                            <a href="customer-view.php?id=<?php echo $tc['id']; ?>">
                                                <?php echo htmlspecialchars($tc['first_name'] . ' ' . $tc['last_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-end fw-bold text-success">৳ <?php echo number_format($tc['total_paid'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($top_customers)): ?>
                                    <tr><td colspan="2" class="text-center py-3 text-muted">No data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Revenue Chart -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue (Last 6 Months)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($monthly_data)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($monthly_data) as $md): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($md['month'] . '-01')); ?></td>
                                        <td class="text-end fw-bold text-success">৳ <?php echo number_format($md['total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center mb-0">No revenue data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

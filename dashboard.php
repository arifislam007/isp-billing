<?php
/**
 * Dashboard Page - Fixed Version
 */

session_start();

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Dashboard - ' . APP_NAME;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get stats
    $stats = [];
    
    // Total customers
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active customers
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
    $stats['active_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total packages
    $stmt = $db->query("SELECT COUNT(*) as count FROM packages WHERE status = 'active'");
    $stats['total_packages'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total invoices this month
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $stmt = $db->query("SELECT COUNT(*) as count FROM invoices WHERE created_at BETWEEN '{$monthStart} 00:00:00' AND '{$monthEnd} 23:59:59'");
    $stats['monthly_invoices'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending invoices
    $stmt = $db->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
    $stats['pending_invoices'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Monthly revenue
    $stmt = $db->query("SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p INNER JOIN invoices i ON p.invoice_id = i.id WHERE p.payment_date BETWEEN '{$monthStart} 00:00:00' AND '{$monthEnd} 23:59:59'");
    $stats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending amount
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'pending'");
    $stats['pending_amount'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // NAS count
    $stmt = $db->query("SELECT COUNT(*) as count FROM nas WHERE status = 'active'");
    $stats['total_nas'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent invoices
    $stmt = $db->query("SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id ORDER BY i.created_at DESC LIMIT 5");
    $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Packages
    $stmt = $db->query("SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC LIMIT 4");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database Error: ' . $e->getMessage();
    $stats = ['total_customers' => 0, 'active_customers' => 0, 'total_packages' => 0, 'monthly_invoices' => 0, 'pending_invoices' => 0, 'monthly_revenue' => 0, 'pending_amount' => 0, 'total_nas' => 0];
    $recentInvoices = [];
    $packages = [];
}

$user = [
    'full_name' => $_SESSION['admin_full_name'] ?? 'Admin',
    'role' => $_SESSION['admin_role'] ?? 'admin'
];

function formatCurrency($amount) {
    return '৳ ' . number_format($amount, 2);
}

function formatSpeed($speedKbps) {
    if ($speedKbps >= 1024) {
        return round($speedKbps / 1024, 2) . ' Mbps';
    }
    return $speedKbps . ' Kbps';
}

function formatBandwidth($bytes) {
    if ($bytes == 0) return 'Unlimited';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    $size = $bytes;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 2) . ' ' . $units[$unitIndex];
}

function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

function getStatusBadgeClass($status) {
    $classes = ['active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger', 'overdue' => 'danger', 'suspended' => 'warning', 'expired' => 'secondary', 'disconnected' => 'danger'];
    return $classes[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    return ucfirst($status);
}
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
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-network-wired me-2"></i><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php"><i class="fas fa-users me-1"></i> Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="packages.php"><i class="fas fa-box me-1"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="invoices.php"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>
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
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3">Dashboard Overview</h2>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Customers</h6>
                                <h2 class="mt-2 mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                        <p class="card-text mt-2 mb-0"><small><?php echo number_format($stats['active_customers']); ?> Active</small></p>
                    </div>
                    <div class="card-footer bg-primary border-0">
                        <a href="customers.php" class="text-white text-decoration-none">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Monthly Revenue</h6>
                                <h2 class="mt-2 mb-0"><?php echo formatCurrency($stats['monthly_revenue']); ?></h2>
                            </div>
                            <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                        </div>
                        <p class="card-text mt-2 mb-0"><small>This month's collection</small></p>
                    </div>
                    <div class="card-footer bg-success border-0">
                        <a href="payments.php" class="text-white text-decoration-none">View Payments</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Pending Invoices</h6>
                                <h2 class="mt-2 mb-0"><?php echo number_format($stats['pending_invoices']); ?></h2>
                            </div>
                            <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                        </div>
                        <p class="card-text mt-2 mb-0"><small><?php echo formatCurrency($stats['pending_amount']); ?> due</small></p>
                    </div>
                    <div class="card-footer bg-warning border-0">
                        <a href="invoices.php" class="text-dark text-decoration-none">View Invoices</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Active NAS</h6>
                                <h2 class="mt-2 mb-0"><?php echo number_format($stats['total_nas']); ?></h2>
                            </div>
                            <i class="fas fa-server fa-3x opacity-50"></i>
                        </div>
                        <p class="card-text mt-2 mb-0"><small>Network Access Servers</small></p>
                    </div>
                    <div class="card-footer bg-info border-0">
                        <a href="nas.php" class="text-white text-decoration-none">Manage NAS</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Invoices</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInvoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td><?php echo formatDate($invoice['due_date']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($invoice['status']); ?>">
                                        <?php echo getStatusLabel($invoice['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentInvoices)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No invoices found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Packages -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Active Packages</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Speed</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pkg['name']); ?></td>
                                <td><?php echo formatSpeed($pkg['download_speed']); ?></td>
                                <td><?php echo formatCurrency($pkg['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($pkg['status']); ?>">
                                        <?php echo getStatusLabel($pkg['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No active packages</td>
                            </tr>
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

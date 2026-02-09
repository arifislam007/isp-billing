<?php
/**
 * Revenue Report Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Revenue Report - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get date filter
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $export = $_GET['export'] ?? '';
    
    // Get payments within date range
    $stmt = $db->prepare("
        SELECT p.*, c.first_name, c.last_name, c.username
        FROM payments p
        LEFT JOIN customers c ON p.customer_id = c.id
        WHERE p.status = 'completed' 
        AND DATE(p.payment_date) BETWEEN ? AND ?
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $total_revenue = 0;
    $cash_payments = 0;
    $bank_payments = 0;
    $mobile_payments = 0;
    
    foreach ($payments as $pay) {
        $amount = floatval($pay['amount']);
        $total_revenue += $amount;
        
        if ($pay['payment_method'] === 'cash') {
            $cash_payments += $amount;
        } elseif ($pay['payment_method'] === 'bank_transfer') {
            $bank_payments += $amount;
        } elseif (in_array($pay['payment_method'], ['mobile_banking', 'card'])) {
            $mobile_payments += $amount;
        }
    }
    
    // Export to CSV
    if ($export === 'csv' && !empty($payments)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="revenue-report-' . $start_date . '-' . $end_date . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Customer', 'Method', 'Amount', 'Notes']);
        foreach ($payments as $pay) {
            fputcsv($output, [
                date('Y-m-d', strtotime($pay['payment_date'])),
                $pay['first_name'] . ' ' . $pay['last_name'] . ' (@' . $pay['username'] . ')',
                $pay['payment_method'],
                $pay['amount'],
                $pay['notes']
            ]);
        }
        fclose($output);
        exit;
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $payments = [];
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
            <h4 class="mb-0">Revenue Report</h4>
            <a href="reports.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Reports</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filter</button>
                    </div>
                    <div class="col-md-3">
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=csv" class="btn btn-success w-100"><i class="fas fa-file-export me-2"></i>Export CSV</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Total Revenue</h5>
                        <h3 class="mb-0">৳ <?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Cash</h5>
                        <h4 class="mb-0">৳ <?php echo number_format($cash_payments, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Bank Transfer</h5>
                        <h4 class="mb-0">৳ <?php echo number_format($bank_payments, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Mobile/Card</h5>
                        <h4 class="mb-0">৳ <?php echo number_format($mobile_payments, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payments Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Payment Details</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                <td>
                                    <a href="customer-view.php?id=<?php echo $pay['customer_id']; ?>">
                                        <?php echo htmlspecialchars($pay['first_name'] . ' ' . $pay['last_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $pay['payment_method'])); ?></td>
                                <td><code><?php echo htmlspecialchars($pay['transaction_id'] ?: '-'); ?></code></td>
                                <td class="text-end fw-bold text-success">৳ <?php echo number_format($pay['amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payments found in this date range</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($payments)): ?>
                        <tfoot>
                            <tr class="table-success">
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($total_revenue, 2); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

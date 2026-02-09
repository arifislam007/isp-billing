<?php
/**
 * Reports Dashboard Page
 */

$pageTitle = 'Reports - ' . APP_NAME;
require_once 'header.php';

// Get date range for filtering
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-t'));

// Revenue by month (last 12 months)
$monthlyRevenue = fetchAll(
    "SELECT 
        DATE_FORMAT(p.payment_date, '%Y-%m') as month,
        DATE_FORMAT(p.payment_date, '%M %Y') as month_name,
        COALESCE(SUM(p.amount), 0) as total
     FROM payments p
     WHERE p.payment_date BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
     ORDER BY month DESC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Revenue by payment method
$revenueByMethod = fetchAll(
    "SELECT payment_method, COALESCE(SUM(amount), 0) as total
     FROM payments
     WHERE payment_date BETWEEN ? AND ?
     GROUP BY payment_method
     ORDER BY total DESC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Top customers by revenue
$topCustomers = fetchAll(
    "SELECT c.id, c.username, CONCAT(c.first_name, ' ', c.last_name) as name,
     COALESCE(SUM(p.amount), 0) as total
     FROM customers c
     LEFT JOIN payments p ON c.id = p.customer_id
     WHERE p.payment_date BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY total DESC
     LIMIT 10",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Customer status distribution
$customerStatus = fetchAll(
    "SELECT status, COUNT(*) as count FROM customers GROUP BY status",
    [],
    'billing'
);

// Invoice status summary
$invoiceSummary = fetch(
    "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount
     FROM invoices
     WHERE created_at BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Package popularity
$packagePopularity = fetchAll(
    "SELECT p.name, COUNT(cp.id) as subscriber_count
     FROM packages p
     LEFT JOIN customer_packages cp ON p.id = cp.package_id AND cp.status = 'active'
     GROUP BY p.id
     ORDER BY subscriber_count DESC",
    [],
    'billing'
);

// Calculate totals
$totalRevenue = fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_date BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
)['total'];

$totalPayments = fetch(
    "SELECT COUNT(*) as count FROM payments WHERE payment_date BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
)['count'];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reports Dashboard</h2>
            <form method="GET" action="" class="d-flex gap-2">
                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Revenue</h6>
                        <h3><?php echo formatCurrency($totalRevenue); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Payments</h6>
                        <h3><?php echo number_format($totalPayments); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Paid Invoices</h6>
                        <h3><?php echo number_format($invoiceSummary['paid_count'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending Amount</h6>
                        <h3><?php echo formatCurrency($invoiceSummary['pending_amount'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3">
            <!-- Monthly Revenue Chart -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Revenue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($monthlyRevenue)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthlyRevenue as $month): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($month['month_name']); ?></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($month['total']); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No revenue data for this period</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Revenue by Payment Method -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>By Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($revenueByMethod)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($revenueByMethod as $method): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <?php
                                    $methodIcons = [
                                        'cash' => '💵 Cash',
                                        'bank_transfer' => '🏦 Bank',
                                        'bkash' => '📱 Bkash',
                                        'nagad' => '📱 Nagad',
                                        'card' => '💳 Card',
                                        'other' => '📋 Other'
                                    ];
                                    echo $methodIcons[$method['payment_method']] ?? ucfirst($method['payment_method']);
                                    ?>
                                </span>
                                <span class="badge bg-primary rounded-pill"><?php echo formatCurrency($method['total']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No payment data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mt-2">
            <!-- Top Customers -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Customers by Revenue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topCustomers)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th class="text-end">Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topCustomers as $index => $customer): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <a href="customer-view.php?id=<?php echo $customer['id']; ?>">
                                                <?php echo htmlspecialchars($customer['name']); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($customer['username']); ?></small>
                                        </td>
                                        <td class="text-end"><strong><?php echo formatCurrency($customer['total']); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No customer data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Package Popularity -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Package Subscribers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($packagePopularity)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Package</th>
                                        <th class="text-end">Active Subscribers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packagePopularity as $package): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($package['name']); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-primary"><?php echo $package['subscriber_count']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No package data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="revenue-report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-file-excel me-1"></i> Revenue Report
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="customer-report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                                   class="btn btn-outline-success w-100">
                                    <i class="fas fa-users me-1"></i> Customer Report
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button onclick="exportToCSV('invoicesTable', 'invoices-<?php echo date('Ymd'); ?>.csv')" 
                                        class="btn btn-outline-warning w-100">
                                    <i class="fas fa-file-csv me-1"></i> Export Invoices
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button onclick="exportToCSV('paymentsTable', 'payments-<?php echo date('Ymd'); ?>.csv')" 
                                        class="btn btn-outline-info w-100">
                                    <i class="fas fa-file-csv me-1"></i> Export Payments
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

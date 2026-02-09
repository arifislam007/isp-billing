<?php
/**
 * Customer Report Page
 */

$pageTitle = 'Customer Report - ' . APP_NAME;
require_once 'header.php';

// Get customer statistics
$customerStats = getCustomerStats();

// Get customers with their payment history
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-t'));

// Customers by status
$statusDistribution = fetchAll(
    "SELECT status, COUNT(*) as count FROM customers GROUP BY status",
    [],
    'billing'
);

// Top paying customers
$topCustomers = fetchAll(
    "SELECT c.id, c.username, CONCAT(c.first_name, ' ', c.last_name) as name,
     c.status, c.created_at,
     COALESCE((
         SELECT SUM(p.amount) FROM payments p 
         WHERE p.customer_id = c.id 
         AND p.payment_date BETWEEN ? AND ?
     ), 0) as total_paid,
     COALESCE((
         SELECT COUNT(*) FROM payments p 
         WHERE p.customer_id = c.id 
         AND p.payment_date BETWEEN ? AND ?
     ), 0) as payment_count
     FROM customers c
     ORDER BY total_paid DESC
     LIMIT 20",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59', $startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// New customers this period
$newCustomers = fetch(
    "SELECT COUNT(*) as count FROM customers WHERE created_at BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customer Report</h2>
            <div>
                <a href="reports.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                </a>
                <button onclick="exportToCSV('customerTable', 'customer-report-<?php echo date('Ymd'); ?>.csv')" 
                        class="btn btn-success">
                    <i class="fas fa-file-csv me-1"></i>Export CSV
                </button>
            </div>
        </div>
        
        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Customers</h6>
                        <h2><?php echo number_format($customerStats['total']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Active Customers</h6>
                        <h2><?php echo number_format($customerStats['active']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Suspended</h6>
                        <h2><?php echo number_format($customerStats['suspended']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">New This Period</h6>
                        <h2><?php echo number_format($newCustomers['count']); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3">
            <!-- Status Distribution -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($statusDistribution as $status): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($status['status']); ?> me-2">
                                        <?php echo getStatusLabel($status['status']); ?>
                                    </span>
                                </span>
                                <span class="badge bg-secondary rounded-pill"><?php echo $status['count']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers by Revenue -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Paying Customers (This Period)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topCustomers)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" id="customerTable">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th class="text-end">Payments</th>
                                        <th class="text-end">Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topCustomers as $index => $customer): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-medal text-warning"></i>
                                            <?php endif; ?>
                                            <?php echo $index + 1; ?>
                                        </td>
                                        <td>
                                            <a href="customer-view.php?id=<?php echo $customer['id']; ?>">
                                                <?php echo htmlspecialchars($customer['name']); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($customer['username']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($customer['status']); ?>">
                                                <?php echo getStatusLabel($customer['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?php echo $customer['payment_count']; ?></td>
                                        <td class="text-end">
                                            <strong><?php echo formatCurrency($customer['total_paid']); ?></strong>
                                        </td>
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
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

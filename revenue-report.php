<?php
/**
 * Revenue Report Page
 */

$pageTitle = 'Revenue Report - ' . APP_NAME;
require_once 'header.php';

$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-t'));

// Get payment summary
$summary = fetch(
    "SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as avg_amount
     FROM payments
     WHERE payment_date BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Get daily revenue
$dailyRevenue = fetchAll(
    "SELECT 
        DATE(payment_date) as date,
        COUNT(*) as transactions,
        COALESCE(SUM(amount), 0) as total
     FROM payments
     WHERE payment_date BETWEEN ? AND ?
     GROUP BY DATE(payment_date)
     ORDER BY date ASC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Get revenue by method
$revenueByMethod = fetchAll(
    "SELECT 
        payment_method,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total
     FROM payments
     WHERE payment_date BETWEEN ? AND ?
     GROUP BY payment_method
     ORDER BY total DESC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);

// Get all payments for the period
$payments = fetchAll(
    "SELECT p.*, i.invoice_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name
     FROM payments p
     LEFT JOIN invoices i ON p.invoice_id = i.id
     LEFT JOIN customers c ON p.customer_id = c.id
     WHERE p.payment_date BETWEEN ? AND ?
     ORDER BY p.payment_date DESC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Revenue Report</h2>
            <div>
                <a href="reports.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                </a>
                <button onclick="exportToCSV('revenueTable', 'revenue-report-<?php echo date('Ymd'); ?>.csv')" 
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
            <div class="col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Revenue</h6>
                        <h2><?php echo formatCurrency($summary['total_amount']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Transactions</h6>
                        <h2><?php echo number_format($summary['total_transactions']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Average Transaction</h6>
                        <h2><?php echo formatCurrency($summary['avg_amount']); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3">
            <!-- Daily Revenue -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Daily Revenue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dailyRevenue)): ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped" id="revenueTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Transactions</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dailyRevenue as $day): ?>
                                    <tr>
                                        <td><?php echo formatDate($day['date']); ?></td>
                                        <td class="text-end"><?php echo number_format($day['transactions']); ?></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($day['total']); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong><?php echo number_format($summary['total_transactions']); ?></strong></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($summary['total_amount']); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No revenue data for this period</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- By Payment Method -->
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
                                    $methodLabels = [
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'bkash' => 'Bkash',
                                        'nagad' => 'Nagad',
                                        'card' => 'Card',
                                        'other' => 'Other'
                                    ];
                                    echo $methodLabels[$method['payment_method']] ?? ucfirst($method['payment_method']);
                                    ?>
                                    <br>
                                    <small class="text-muted"><?php echo $method['count']; ?> transactions</small>
                                </span>
                                <strong><?php echo formatCurrency($method['total']); ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-4 mb-0">No data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

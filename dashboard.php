<?php
/**
 * Dashboard Page
 */

$pageTitle = 'Dashboard - ' . APP_NAME;
require_once 'header.php';

$stats = getDashboardStats();
?>

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
                <p class="card-text mt-2 mb-0">
                    <small><?php echo number_format($stats['active_customers']); ?> Active</small>
                </p>
            </div>
            <div class="card-footer bg-primary border-0">
                <a href="customers.php" class="text-white text-decoration-none">View All Customers</a>
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
                <p class="card-text mt-2 mb-0">
                    <small>This month's collection</small>
                </p>
            </div>
            <div class="card-footer bg-success border-0">
                <a href="revenue-report.php" class="text-white text-decoration-none">View Revenue Report</a>
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
                <p class="card-text mt-2 mb-0">
                    <small><?php echo formatCurrency($stats['pending_amount']); ?> due</small>
                </p>
            </div>
            <div class="card-footer bg-warning border-0">
                <a href="invoices.php?status=pending" class="text-dark text-decoration-none">View Pending</a>
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
                <p class="card-text mt-2 mb-0">
                    <small>Network Access Servers</small>
                </p>
            </div>
            <div class="card-footer bg-info border-0">
                <a href="nas.php" class="text-white text-decoration-none">Manage NAS</a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions and Recent Activity -->
<div class="row g-3">
    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add-customer.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i>Add New Customer
                    </a>
                    <a href="add-package.php" class="btn btn-outline-success">
                        <i class="fas fa-box me-2"></i>Create Package
                    </a>
                    <a href="add-invoice.php" class="btn btn-outline-warning">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Generate Invoice
                    </a>
                    <a href="add-payment.php" class="btn btn-outline-info">
                        <i class="fas fa-money-bill-alt me-2"></i>Record Payment
                    </a>
                    <a href="add-nas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-server me-2"></i>Add NAS Device
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Invoices -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Invoices</h5>
                <a href="invoices.php" class="btn btn-sm btn-light">View All</a>
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
                            <?php
                            $recentInvoices = fetchAll(
                                "SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
                                 FROM invoices i 
                                 LEFT JOIN customers c ON i.customer_id = c.id 
                                 ORDER BY i.created_at DESC LIMIT 5",
                                [],
                                'billing'
                            );
                            
                            foreach ($recentInvoices as $invoice):
                            ?>
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
    </div>
</div>

<!-- Customer Status Overview -->
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Customer Status Overview</h5>
            </div>
            <div class="card-body">
                <?php $customerStats = getCustomerStats(); ?>
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-success">
                            <h4><?php echo number_format($customerStats['active']); ?></h4>
                            <small>Active</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-secondary">
                            <h4><?php echo number_format($customerStats['inactive']); ?></h4>
                            <small>Inactive</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-warning">
                            <h4><?php echo number_format($customerStats['suspended']); ?></h4>
                            <small>Suspended</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-danger">
                            <h4><?php echo number_format($customerStats['disconnected']); ?></h4>
                            <small>Disconnected</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
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
                            <?php
                            $packages = fetchAll(
                                "SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC LIMIT 4",
                                [],
                                'billing'
                            );
                            
                            foreach ($packages as $pkg):
                            ?>
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
</div>

<?php require_once 'footer.php'; ?>

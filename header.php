<?php
/**
 * Common Header for all pages
 */

requireLogin();
require_once 'functions.php';

$user = getCurrentUser();
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-network-wired me-2"></i><?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['customers.php', 'add-customer.php', 'customer-view.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i> Customers
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="customers.php">All Customers</a></li>
                            <li><a class="dropdown-item" href="add-customer.php">Add New Customer</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['packages.php', 'add-package.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box me-1"></i> Packages
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="packages.php">All Packages</a></li>
                            <li><a class="dropdown-item" href="add-package.php">Add New Package</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['invoices.php', 'add-invoice.php', 'invoice-view.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-invoice me-1"></i> Invoices
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="invoices.php">All Invoices</a></li>
                            <li><a class="dropdown-item" href="add-invoice.php">Create Invoice</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payments.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-money-bill-wave me-1"></i> Payments
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="payments.php">All Payments</a></li>
                            <li><a class="dropdown-item" href="add-payment.php">Record Payment</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['reports.php', 'revenue-report.php', 'customer-report.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reports.php">Dashboard Reports</a></li>
                            <li><a class="dropdown-item" href="revenue-report.php">Revenue Report</a></li>
                            <li><a class="dropdown-item" href="customer-report.php">Customer Report</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['nas.php', 'add-nas.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-server me-1"></i> NAS
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="nas.php">All NAS</a></li>
                            <li><a class="dropdown-item" href="add-nas.php">Add New NAS</a></li>
                        </ul>
                    </li>
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin-users.php', 'add-admin.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i> Admin Users
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin-users.php">All Admin Users</a></li>
                            <li><a class="dropdown-item" href="add-admin.php">Add New Admin</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="change-password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flashMessage['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

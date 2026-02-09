<?php
/**
 * Customer View Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Customer Details - ' . APP_NAME;
$error = '';
$customer = null;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: customers.php');
        exit;
    }
    
    $stmt = $db->prepare("SELECT c.*, p.name as package_name, p.price as package_price, p.speed_down, p.speed_up FROM customers c LEFT JOIN packages p ON c.package_id = p.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header('Location: customers.php');
        exit;
    }
    
    // Get invoices
    $stmt = $db->prepare("SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payments
    $stmt = $db->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY payment_date DESC LIMIT 10");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

// Status badge helper
function getStatusBadge($status) {
    $badges = [
        'active' => 'bg-success',
        'pending' => 'bg-warning text-dark',
        'suspended' => 'bg-danger',
        'disconnected' => 'bg-secondary'
    ];
    return $badges[$status] ?? 'bg-secondary';
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-network-wired me-2"></i><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="customers.php"><i class="fas fa-users me-1"></i> Customers</a></li>
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
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($customer): ?>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="profile-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h3>
                            <p class="mb-0 opacity-75">@<?php echo htmlspecialchars($customer['username']); ?></p>
                        </div>
                        <div class="text-end">
                            <span class="badge <?php echo getStatusBadge($customer['status']); ?> fs-6"><?php echo ucfirst($customer['status']); ?></span>
                            <div class="mt-2">
                                <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-light btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                                <a href="assign-package.php?id=<?php echo $customer['id']; ?>" class="btn btn-light btn-sm"><i class="fas fa-box me-1"></i>Change Package</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 120px;">Email:</td>
                                <td><?php echo htmlspecialchars($customer['email'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Phone:</td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Address:</td>
                                <td><?php echo nl2br(htmlspecialchars($customer['address'] ?: '-')); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created:</td>
                                <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-box me-2"></i>Current Package</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($customer['package_id']): ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($customer['package_name']); ?></h5>
                        <p class="card-text">
                            <strong class="fs-4 text-success">৳ <?php echo number_format($customer['package_price'], 2); ?></strong> / month
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-down me-2 text-primary"></i>Download: <?php echo htmlspecialchars($customer['speed_down'] ?: 'Unlimited'); ?></li>
                            <li><i class="fas fa-arrow-up me-2 text-success"></i>Upload: <?php echo htmlspecialchars($customer['speed_up'] ?: 'Unlimited'); ?></li>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted">No package assigned</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-network-wired me-2"></i>Connection Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted">Type:</td>
                                <td><?php echo strtoupper($customer['conn_type']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Router IP:</td>
                                <td><?php echo htmlspecialchars($customer['router_ip'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">MAC Address:</td>
                                <td><code><?php echo htmlspecialchars($customer['mac_address'] ?: '-'); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Invoices</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Amount</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><a href="invoice-view.php?id=<?php echo $inv['id']; ?>"><?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                    <td>৳ <?php echo number_format($inv['total_amount'], 2); ?></td>
                                    <td><?php echo date('M Y', strtotime($inv['billing_period_start'])); ?></td>
                                    <td><span class="badge <?php echo $inv['status'] === 'paid' ? 'bg-success' : ($inv['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($invoices)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No invoices found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Recent Payments</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                    <td class="text-success fw-bold">৳ <?php echo number_format($pay['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($payments)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No payments found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

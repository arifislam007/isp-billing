<?php
/**
 * Invoice View Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Invoice Details - ' . APP_NAME;
$error = '';
$invoice = null;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: invoices.php');
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT i.*, c.first_name, c.last_name, c.username, c.email, c.phone, c.address,
               p.name as package_name, p.price as package_price
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN packages p ON i.package_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        header('Location: invoices.php');
        exit;
    }
    
    // Get payments for this invoice
    $stmt = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $paid_amount = array_sum(array_column($payments, 'amount'));
    $due_amount = $invoice['total_amount'] - $paid_amount;
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

// Status badge helper
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning text-dark',
        'paid' => 'bg-success',
        'overdue' => 'bg-danger',
        'cancelled' => 'bg-secondary'
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
        .invoice-header { background: #2c3e50; color: white; padding: 30px; border-radius: 10px 10px 0 0; }
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
                    <li class="nav-item"><a class="nav-link active" href="invoices.php"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>
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
        <?php elseif ($invoice): ?>
        
        <div class="card">
            <div class="invoice-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">INVOICE</h3>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    </div>
                    <div class="text-end">
                        <span class="badge <?php echo getStatusBadge($invoice['status']); ?> fs-6"><?php echo ucfirst($invoice['status']); ?></span>
                        <div class="mt-2">
                            <a href="invoice-print.php?id=<?php echo $id; ?>" class="btn btn-light btn-sm" target="_blank"><i class="fas fa-print me-1"></i>Print</a>
                            <a href="invoices.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Bill To:</h6>
                        <h5><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></h5>
                        <p class="mb-0">@<?php echo htmlspecialchars($invoice['username']); ?></p>
                        <?php if ($invoice['email']): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($invoice['email']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['phone']): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($invoice['phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['address']): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Invoice Details:</h6>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Invoice Number:</td>
                                <td class="fw-bold"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            </tr>
                            <tr>
                                <td>Invoice Date:</td>
                                <td><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td>Due Date:</td>
                                <td><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                            </tr>
                            <tr>
                                <td>Billing Period:</td>
                                <td><?php echo date('M Y', strtotime($invoice['billing_period_start'])); ?> - <?php echo date('M Y', strtotime($invoice['billing_period_end'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Package Price</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($invoice['package_name'] ?: 'Service'); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($invoice['billing_period_start']); ?> to <?php echo htmlspecialchars($invoice['billing_period_end']); ?></small>
                                </td>
                                <td class="text-end">৳ <?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="text-end">৳ <?php echo number_format($invoice['tax_amount'], 2); ?></td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Total Amount:</td>
                                <td class="text-end fw-bold fs-5">৳ <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Paid:</td>
                                <td class="text-end text-success">- ৳ <?php echo number_format($paid_amount, 2); ?></td>
                            </tr>
                            <tr class="<?php echo $due_amount > 0 ? 'table-warning' : 'table-success'; ?>">
                                <td colspan="3" class="text-end">Due Amount:</td>
                                <td class="text-end fw-bold">৳ <?php echo number_format(max(0, $due_amount), 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if ($invoice['notes']): ?>
                <div class="alert alert-info">
                    <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($payments)): ?>
                <h6 class="mt-4 mb-3">Payment History</h6>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pay['payment_method']))); ?></td>
                            <td><code><?php echo htmlspecialchars($pay['transaction_id'] ?: '-'); ?></code></td>
                            <td class="text-end text-success fw-bold">৳ <?php echo number_format($pay['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php if ($due_amount > 0 && $invoice['status'] !== 'cancelled'): ?>
                <div class="mt-4">
                    <a href="add-payment.php" class="btn btn-success"><i class="fas fa-money-bill me-2"></i>Record Payment</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

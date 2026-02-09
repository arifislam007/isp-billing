<?php
/**
 * Add Invoice Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Create Invoice - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get customers
    $customers = $db->query("SELECT id, username, first_name, last_name FROM customers WHERE status != 'disconnected' ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get packages
    $packages = $db->query("SELECT id, name, price FROM packages WHERE status = 'active' ORDER BY price")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $package_id = intval($_POST['package_id'] ?? 0);
            $billing_period_start = $_POST['billing_period_start'] ?? '';
            $billing_period_end = $_POST['billing_period_end'] ?? '';
            $due_date = $_POST['due_date'] ?? '';
            $tax_amount = floatval($_POST['tax_amount'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            if ($customer_id <= 0 || $package_id <= 0 || empty($billing_period_start) || empty($billing_period_end) || empty($due_date)) {
                $error = 'Please fill in all required fields';
            } else {
                $package = $db->prepare("SELECT * FROM packages WHERE id = ?")->fetch(PDO::FETCH_ASSOC);
                if (!$package) {
                    $error = 'Package not found';
                } else {
                    $total_amount = $package['price'] + $tax_amount;
                    $invoice_number = 'INV' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
                    
                    $stmt = $db->prepare("INSERT INTO invoices (invoice_number, customer_id, package_id, billing_period_start, billing_period_end, amount, tax_amount, total_amount, status, due_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
                    $stmt->execute([$invoice_number, $customer_id, $package_id, $billing_period_start, $billing_period_end, $package['price'], $tax_amount, $total_amount, $due_date, $notes]);
                    
                    header('Location: invoices.php');
                    exit;
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $customers = [];
    $packages = [];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Create Invoice</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Customer *</label>
                                <select class="form-select" name="customer_id" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (@' . $c['username'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Package *</label>
                                <select class="form-select" name="package_id" id="packageSelect" required onchange="updateAmount()">
                                    <option value="">-- Select Package --</option>
                                    <?php foreach ($packages as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>"><?php echo htmlspecialchars($p['name']); ?> - ৳ <?php echo number_format($p['price'], 2); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Period Start *</label>
                                    <input type="date" class="form-control" name="billing_period_start" value="<?php echo date('Y-m-01'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Period End *</label>
                                    <input type="date" class="form-control" name="billing_period_end" value="<?php echo date('Y-m-t'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Due Date *</label>
                                    <input type="date" class="form-control" name="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Package Amount</label>
                                    <input type="number" class="form-control" id="amount" value="0.00" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tax Amount</label>
                                    <input type="number" class="form-control" name="tax_amount" value="0.00" min="0" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Total</label>
                                    <input type="number" class="form-control" id="total" value="0.00" readonly style="font-weight: bold;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Create Invoice</button>
                                <a href="invoices.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateAmount() {
        const select = document.getElementById('packageSelect');
        const option = select.options[select.selectedIndex];
        const price = parseFloat(option.dataset.price) || 0;
        const tax = parseFloat(document.querySelector('[name="tax_amount"]').value) || 0;
        document.getElementById('amount').value = price.toFixed(2);
        document.getElementById('total').value = (price + tax).toFixed(2);
    }
    document.querySelector('[name="tax_amount"]').addEventListener('change', updateAmount);
    </script>
</body>
</html>

<?php
/**
 * Add Payment Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Add Payment - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get customers with pending invoices
    $customers = $db->query("
        SELECT c.id, c.first_name, c.last_name, c.username, 
               COALESCE(SUM(i.total_amount - COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = i.id), 0)), 0) as due_amount
        FROM customers c
        LEFT JOIN invoices i ON c.id = i.customer_id AND i.status = 'pending'
        GROUP BY c.id
        HAVING due_amount > 0
        ORDER BY c.first_name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $transaction_id = $_POST['transaction_id'] ?? '';
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $notes = $_POST['notes'] ?? '';
            
            if ($customer_id <= 0 || $amount <= 0) {
                $error = 'Please select a customer and enter amount';
            } else {
                $stmt = $db->prepare("INSERT INTO payments (customer_id, amount, payment_method, transaction_id, payment_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$customer_id, $amount, $payment_method, $transaction_id, $payment_date, $notes]);
                
                // Update pending invoices
                $remaining = $amount;
                $stmt = $db->prepare("SELECT * FROM invoices WHERE customer_id = ? AND status = 'pending' ORDER BY due_date");
                $stmt->execute([$customer_id]);
                $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($invoices as $inv) {
                    if ($remaining <= 0) break;
                    
                    $inv_total = $inv['total_amount'];
                    $stmt = $db->prepare("SELECT SUM(amount) as paid FROM payments WHERE invoice_id = ?");
                    $stmt->execute([$inv['id']]);
                    $paid = $stmt->fetch(PDO::FETCH_ASSOC);
                    $already_paid = floatval($paid['paid'] ?? 0);
                    $due = $inv_total - $already_paid;
                    
                    if ($due > 0) {
                        $pay_for_inv = min($remaining, $due);
                        
                        $stmt = $db->prepare("INSERT INTO payments (customer_id, invoice_id, amount, payment_method, transaction_id, payment_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$customer_id, $inv['id'], $pay_for_inv, $payment_method, $transaction_id, $payment_date, $notes]);
                        
                        $remaining -= $pay_for_inv;
                        
                        // Check if fully paid
                        $new_paid = $already_paid + $pay_for_inv;
                        if ($new_paid >= $inv_total) {
                            $stmt = $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                            $stmt->execute([$inv['id']]);
                        }
                    }
                }
                
                header('Location: payments.php');
                exit;
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $customers = [];
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
                    <li class="nav-item"><a class="nav-link" href="invoices.php"><i class="fas fa-file-invoice me-1"></i> Invoices</a></li>
                    <li class="nav-item"><a class="nav-link active" href="payments.php"><i class="fas fa-money-bill-wave me-1"></i> Payments</a></li>
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
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Add Payment</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Customer *</label>
                                <select class="form-select" name="customer_id" id="customerSelect" required onchange="updateDue()">
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" data-due="<?php echo $c['due_amount']; ?>">
                                        <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (@' . $c['username'] . ')'); ?> - Due: ৳ <?php echo number_format($c['due_amount'], 2); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Amount (BDT) *</label>
                                    <input type="number" class="form-control" name="amount" id="amountInput" value="0.00" min="0.01" step="0.01" required>
                                    <small class="text-muted">Max due: <span id="maxDue">0.00</span></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="mobile_banking">Mobile Banking</option>
                                        <option value="card">Card</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Transaction ID</label>
                                    <input type="text" class="form-control" name="transaction_id" placeholder="Optional">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Record Payment</button>
                                <a href="payments.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateDue() {
        const select = document.getElementById('customerSelect');
        const option = select.options[select.selectedIndex];
        const due = parseFloat(option.dataset.due) || 0;
        document.getElementById('maxDue').textContent = due.toFixed(2);
        document.getElementById('amountInput').value = due.toFixed(2);
    }
    </script>
</body>
</html>

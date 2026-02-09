<?php
/**
 * Assign Package Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Assign Package - ' . APP_NAME;
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
    
    // Get packages for selection
    $packages = $db->query("SELECT id, name, price, speed_down, speed_up FROM packages WHERE status = 'active' ORDER BY price")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $package_id = intval($_POST['package_id'] ?? 0);
            $router_ip = $_POST['router_ip'] ?? '';
            $mac_address = $_POST['mac_address'] ?? '';
            
            // Update customer
            $stmt = $db->prepare("UPDATE customers SET package_id=?, router_ip=?, mac_address=? WHERE id=?");
            $stmt->execute([$package_id, $router_ip, $mac_address, $id]);
            
            // Update FreeRADIUS attributes
            $username = $customer['username'];
            
            // Delete old rate limit
            $stmt = $db->prepare("DELETE FROM radreply WHERE username = ? AND attribute = 'Mikrotik-Rate-Limit'");
            $stmt->execute([$username]);
            
            if ($package_id > 0) {
                $pkg = null;
                foreach ($packages as $p) {
                    if ($p['id'] == $package_id) {
                        $pkg = $p;
                        break;
                    }
                }
                
                if ($pkg && !empty($pkg['speed_down']) || !empty($pkg['speed_up'])) {
                    $rate_limit = ($pkg['speed_down'] ? $pkg['speed_down'] : '0') . '/' . ($pkg['speed_up'] ? $pkg['speed_up'] : '0');
                    $stmt = $db->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Mikrotik-Rate-Limit', ':=', ?)");
                    $stmt->execute([$username, $rate_limit]);
                }
            }
            
            header('Location: customer-view.php?id=' . $id);
            exit;
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px/10px rgba(0,0,0,0.1); }
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($customer): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                                <p class="mb-0 text-muted">@<?php echo htmlspecialchars($customer['username']); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo ($customer['status'] ?? '') === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($customer['status'] ?? 'Unknown'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Assign Package</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Package *</label>
                                <select class="form-select" name="package_id" required>
                                    <option value="0">-- No Package --</option>
                                    <?php foreach ($packages as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($customer['package_id'] ?? 0) == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name']); ?> - ৳ <?php echo number_format($p['price'], 2); ?>
                                        (<?php echo htmlspecialchars($p['speed_down'] ?: '0'); ?>/<?php echo htmlspecialchars($p['speed_up'] ?: '0'); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Router IP</label>
                                    <input type="text" class="form-control" name="router_ip" value="<?php echo htmlspecialchars($customer['router_ip'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">MAC Address</label>
                                    <input type="text" class="form-control" name="mac_address" value="<?php echo htmlspecialchars($customer['mac_address'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Assign Package</button>
                                <a href="customer-view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

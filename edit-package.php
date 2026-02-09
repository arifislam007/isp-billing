<?php
/**
 * Edit Package Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Edit Package - ' . APP_NAME;
$error = '';
$package = null;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: packages.php');
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        header('Location: packages.php');
        exit;
    }
    
    // Get NAS devices for selection
    $nas_devices = $db->query("SELECT id, shortname, nasname FROM nas WHERE status = 'active' ORDER BY shortname")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $name = $_POST['name'] ?? '';
            $speed_down = $_POST['speed_down'] ?? '';
            $speed_up = $_POST['speed_up'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $billing_cycle = $_POST['billing_cycle'] ?? 'monthly';
            $nas_id = intval($_POST['nas_id'] ?? 0);
            $radgroupreply = $_POST['radgroupreply'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name) || $price < 0) {
                $error = 'Please fill in package name and price';
            } else {
                $stmt = $db->prepare("UPDATE packages SET name=?, speed_down=?, speed_up=?, price=?, billing_cycle=?, nas_id=?, radgroupreply=?, description=?, status=? WHERE id=?");
                $stmt->execute([$name, $speed_down, $speed_up, $price, $billing_cycle, $nas_id, $radgroupreply, $description, $status, $id]);
                
                header('Location: packages.php');
                exit;
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $nas_devices = [];
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
                    <li class="nav-item"><a class="nav-link active" href="packages.php"><i class="fas fa-box me-1"></i> Packages</a></li>
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
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Package</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Package Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($package['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Price (BDT) *</label>
                                    <input type="number" class="form-control" name="price" value="<?php echo number_format($package['price'] ?? 0, 2, '.', ''); ?>" min="0" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Download Speed</label>
                                    <input type="text" class="form-control" name="speed_down" value="<?php echo htmlspecialchars($package['speed_down'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Upload Speed</label>
                                    <input type="text" class="form-control" name="speed_up" value="<?php echo htmlspecialchars($package['speed_up'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Billing Cycle</label>
                                    <select class="form-select" name="billing_cycle">
                                        <option value="monthly" <?php echo ($package['billing_cycle'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="quarterly" <?php echo ($package['billing_cycle'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                        <option value="yearly" <?php echo ($package['billing_cycle'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NAS Device</label>
                                    <select class="form-select" name="nas_id">
                                        <option value="0">-- Default --</option>
                                        <?php foreach ($nas_devices as $nas): ?>
                                        <option value="<?php echo $nas['id']; ?>" <?php echo ($package['nas_id'] ?? 0) == $nas['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($nas['shortname'] ?: $nas['nasname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">RADIUS Group Reply Attributes</label>
                                <textarea class="form-control" name="radgroupreply" rows="4"><?php echo htmlspecialchars($package['radgroupreply'] ?? ''); ?></textarea>
                                <small class="text-muted">Enter one attribute per line for FreeRADIUS</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo ($package['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($package['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Update Package</button>
                                <a href="packages.php" class="btn btn-secondary">Cancel</a>
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

<?php
/**
 * Add Customer Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Add New Customer - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get packages for selection
    $packages = $db->query("SELECT id, name, price, speed_down, speed_up FROM packages WHERE status = 'active' ORDER BY price")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = $_POST['address'] ?? '';
            $package_id = intval($_POST['package_id'] ?? 0);
            $conn_type = $_POST['conn_type'] ?? 'pppoe';
            $router_ip = $_POST['router_ip'] ?? '';
            $mac_address = $_POST['mac_address'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            // Validation
            if (empty($username) || empty($password) || empty($first_name) || empty($last_name)) {
                $error = 'Please fill in all required fields (Username, Password, First Name, Last Name)';
            } else {
                // Check if username exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username already exists';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO customers (username, password, first_name, last_name, email, phone, address, package_id, conn_type, router_ip, mac_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $first_name, $last_name, $email, $phone, $address, $package_id, $conn_type, $router_ip, $mac_address, $status]);
                    
                    $customer_id = $db->lastInsertId();
                    
                    // Create FreeRADIUS entry if PPPoE
                    if ($conn_type === 'pppoe' && $package_id > 0) {
                        $stmt = $db->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)");
                        $stmt->execute([$username, $password]);
                        
                        // Add package attributes
                        $pkg = null;
                        foreach ($packages as $p) {
                            if ($p['id'] == $package_id) {
                                $pkg = $p;
                                break;
                            }
                        }
                        
                        if ($pkg) {
                            // Add Mikrotik rate limit if specified
                            if (!empty($pkg['speed_down']) || !empty($pkg['speed_up'])) {
                                $rate_limit = ($pkg['speed_down'] ? $pkg['speed_down'] : '0') . '/' . ($pkg['speed_up'] ? $pkg['speed_up'] : '0');
                                $stmt = $db->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Mikrotik-Rate-Limit', ':=', ?)");
                                $stmt->execute([$username, $rate_limit]);
                            }
                        }
                    }
                    
                    header('Location: customers.php');
                    exit;
                }
            }
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
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <h6 class="text-muted mb-3">Login Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Connection Type</label>
                                    <select class="form-select" name="conn_type">
                                        <option value="pppoe">PPPoE</option>
                                        <option value="dhcp">DHCP/Static IP</option>
                                        <option value="hotspot">Hotspot</option>
                                    </select>
                                </div>
                            </div>
                            
                            <h6 class="text-muted mb-3 mt-4">Personal Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            
                            <h6 class="text-muted mb-3 mt-4">Package & Connection</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Assign Package</label>
                                    <select class="form-select" name="package_id">
                                        <option value="0">-- No Package --</option>
                                        <?php foreach ($packages as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> - ৳ <?php echo number_format($p['price'], 2); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Router IP</label>
                                    <input type="text" class="form-control" name="router_ip" placeholder="192.168.x.x">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">MAC Address</label>
                                    <input type="text" class="form-control" name="mac_address" placeholder="XX:XX:XX:XX:XX:XX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active">Active</option>
                                        <option value="pending">Pending</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="disconnected">Disconnected</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Customer</button>
                                <a href="customers.php" class="btn btn-secondary">Cancel</a>
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

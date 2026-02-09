<?php
/**
 * Add NAS Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Add New NAS - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $nasname = $_POST['nasname'] ?? '';
            $shortname = $_POST['shortname'] ?? '';
            $type = $_POST['type'] ?? 'other';
            $ports = intval($_POST['ports'] ?? 0);
            $secret = $_POST['secret'] ?? '';
            $server = $_POST['server'] ?? '';
            $community = $_POST['community'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (empty($nasname) || empty($secret)) {
                $error = 'Please fill in NAS IP and Secret';
            } else {
                $stmt = $db->prepare("INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$nasname, $shortname, $type, $ports, $secret, $server, $community, $description]);
                
                header('Location: nas.php');
                exit;
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
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
                    <li class="nav-item"><a class="nav-link" href="payments.php"><i class="fas fa-money-bill-wave me-1"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link active" href="nas.php"><i class="fas fa-server me-1"></i> NAS</a></li>
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
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-server me-2"></i>Add New NAS</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">NAS IP/Hostname *</label>
                                    <input type="text" class="form-control" name="nasname" placeholder="192.168.1.1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Short Name</label>
                                    <input type="text" class="form-control" name="shortname" placeholder="Router1">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="other">Other</option>
                                        <option value="mikrotik">MikroTik</option>
                                        <option value="cisco">Cisco</option>
                                        <option value="ubiquiti">Ubiquiti</option>
                                        <option value="huawei">Huawei</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ports</label>
                                    <input type="number" class="form-control" name="ports" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">RADIUS Secret *</label>
                                    <input type="text" class="form-control" name="secret" placeholder="Shared secret" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Auth Server</label>
                                    <input type="text" class="form-control" name="server" placeholder="Optional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SNMP Community</label>
                                    <input type="text" class="form-control" name="community" placeholder="Optional">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-info"><i class="fas fa-save me-2"></i>Add NAS</button>
                                <a href="nas.php" class="btn btn-secondary">Cancel</a>
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

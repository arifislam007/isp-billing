<?php
/**
 * Edit Admin Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Edit Admin - ' . APP_NAME;
$error = '';
$admin = null;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: admin-users.php');
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        header('Location: admin-users.php');
        exit;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'admin';
            $status = $_POST['status'] ?? 'active';
            
            if (empty($username) || empty($full_name)) {
                $error = 'Please fill in all required fields';
            } else {
                // Check if username exists (excluding current admin)
                $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username already exists';
                } else {
                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE admin_users SET username=?, password=?, full_name=?, email=?, role=?, status=? WHERE id=?");
                        $stmt->execute([$username, $password_hash, $full_name, $email, $role, $status, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE admin_users SET username=?, full_name=?, email=?, role=?, status=? WHERE id=?");
                        $stmt->execute([$username, $full_name, $email, $role, $status, $id]);
                    }
                    
                    header('Location: admin-users.php');
                    exit;
                }
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
                    <li class="nav-item"><a class="nav-link" href="nas.php"><i class="fas fa-server me-1"></i> NAS</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" data-bs-toggle="dropdown"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['full_name']); ?></a>
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
            <div class="col-lg-6 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Admin</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password (leave blank to keep)</label>
                                <input type="password" class="form-control" name="password" minlength="6">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role">
                                        <option value="superadmin" <?php echo ($admin['role'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                                        <option value="admin" <?php echo ($admin['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="manager" <?php echo ($admin['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="support" <?php echo ($admin['role'] ?? '') === 'support' ? 'selected' : ''; ?>>Support</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo ($admin['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($admin['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Admin</button>
                                <a href="admin-users.php" class="btn btn-secondary">Cancel</a>
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

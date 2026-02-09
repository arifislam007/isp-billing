<?php
/**
 * Admin Users Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Admin Users - ' . APP_NAME;
$error = '';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle delete action
    if (isset($_GET['delete']) && $_GET['delete'] > 0) {
        $delete_id = intval($_GET['delete']);
        if ($delete_id != $_SESSION['admin_id']) {
            $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$delete_id]);
            header('Location: admin-users.php');
            exit;
        } else {
            $error = 'You cannot delete your own account';
        }
    }
    
    // Get all admin users
    $admins = $db->query("SELECT id, username, full_name, email, role, status, created_at FROM admin_users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $admins = [];
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

// Status badge helper
function getStatusBadge($status) {
    return $status === 'active' ? 'bg-success' : 'bg-secondary';
}

// Role badge helper
function getRoleBadge($role) {
    $badges = [
        'superadmin' => 'bg-danger',
        'admin' => 'bg-primary',
        'manager' => 'bg-info text-dark',
        'support' => 'bg-warning text-dark'
    ];
    return $badges[$role] ?? 'bg-secondary';
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Admin Users</h4>
            <a href="add-admin.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Admin</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email'] ?: '-'); ?></td>
                                <td><span class="badge <?php echo getRoleBadge($admin['role']); ?>"><?php echo ucfirst($admin['role']); ?></span></td>
                                <td><span class="badge <?php echo getStatusBadge($admin['status']); ?>"><?php echo ucfirst($admin['status']); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($admin['created_at'])); ?></td>
                                <td class="text-end">
                                    <a href="edit-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <a href="admin-users.php?delete=<?php echo $admin['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this admin?')"><i class="fas fa-trash"></i></a>
                                    <?php else: ?>
                                    <span class="text-muted small">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($admins)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No admin users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

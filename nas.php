<?php
/**
 * NAS Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'NAS Management - ' . APP_NAME;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle status update or delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === ($_SESSION['csrf_token'] ?? '')) {
            $action = $_POST['action'];
            $nas_id = intval($_POST['nas_id'] ?? 0);
            
            if ($action === 'update_status' && $nas_id > 0) {
                $status = $_POST['status'] ?? 'active';
                $stmt = $db->prepare("UPDATE nas SET status = ? WHERE id = ?");
                $stmt->execute([$status, $nas_id]);
            } elseif ($action === 'delete' && $nas_id > 0) {
                $stmt = $db->prepare("DELETE FROM nas WHERE id = ?");
                $stmt->execute([$nas_id]);
            }
        }
        header('Location: nas.php');
        exit;
    }
    
    $stmt = $db->query("SELECT * FROM nas ORDER BY created_at DESC");
    $nasList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    $nasList = [];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

function getStatusBadgeClass($status) { $classes = ['active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger']; return $classes[$status] ?? 'secondary'; }
function getStatusLabel($status) { return ucfirst($status); }
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
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-server me-2"></i>NAS Management</h2>
            <a href="add-nas.php" class="btn btn-info"><i class="fas fa-plus me-1"></i> Add New NAS</a>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($nasList)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-server fa-4x text-muted mb-3"></i>
                    <p>No NAS devices found. Add your first NAS!</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>NAS IP/Name</th>
                                <th>Short Name</th>
                                <th>Type</th>
                                <th>Secret</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nasList as $nas): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($nas['nasname']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars(substr($nas['description'] ?? '', 0, 50)); ?></small></td>
                                <td><?php echo htmlspecialchars($nas['shortname'] ?? '-'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($nas['type'])); ?></span></td>
                                <td><code><?php echo htmlspecialchars(substr($nas['secret'], 0, 8)); ?>...</code></td>
                                <td><span class="badge bg-<?php echo getStatusBadgeClass($nas['status']); ?>"><?php echo getStatusLabel($nas['status']); ?></span></td>
                                <td>
                                    <a href="edit-nas.php?id=<?php echo $nas['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this NAS?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="nas_id" value="<?php echo $nas['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

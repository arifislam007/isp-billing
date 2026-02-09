<?php
/**
 * Packages Page - Self-contained version
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Packages - ' . APP_NAME;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle status update or delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if ($csrf_token === $_SESSION['csrf_token'] ?? '') {
            $action = $_POST['action'];
            $package_id = intval($_POST['package_id'] ?? 0);
            
            if ($action === 'update_status' && $package_id > 0) {
                $status = $_POST['status'] ?? 'active';
                $stmt = $db->prepare("UPDATE packages SET status = ? WHERE id = ?");
                $stmt->execute([$status, $package_id]);
            } elseif ($action === 'delete' && $package_id > 0) {
                $stmt = $db->prepare("DELETE FROM packages WHERE id = ?");
                $stmt->execute([$package_id]);
            }
        }
        header('Location: packages.php');
        exit;
    }
    
    // Get packages with subscriber count
    $stmt = $db->query("
        SELECT p.*, 
        (SELECT COUNT(*) FROM customer_packages WHERE package_id = p.id AND status = 'active') as subscriber_count
        FROM packages p 
        ORDER BY p.price ASC
    ");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    $packages = [];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = ['full_name' => $_SESSION['admin_full_name'] ?? 'Admin'];

function formatCurrency($amount) { return '৳ ' . number_format($amount, 2); }
function formatSpeed($speedKbps) { if ($speedKbps >= 1024) { return round($speedKbps / 1024, 2) . ' Mbps'; } return $speedKbps . ' Kbps'; }
function formatBandwidth($bytes) { if ($bytes == 0) return 'Unlimited'; $units = ['B', 'KB', 'MB', 'GB', 'TB']; $unitIndex = 0; $size = $bytes; while ($size >= 1024 && $unitIndex < count($units) - 1) { $size /= 1024; $unitIndex++; } return round($size, 2) . ' ' . $units[$unitIndex]; }
function getStatusBadgeClass($status) { $classes = ['active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger', 'overdue' => 'danger', 'suspended' => 'warning', 'expired' => 'secondary', 'disconnected' => 'danger']; return $classes[$status] ?? 'secondary'; }
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
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .package-card { transition: all 0.3s; }
        .package-card:hover { transform: translateY(-5px); border-color: #667eea; }
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
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box me-2"></i>Package Management</h2>
            <a href="add-package.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add New Package</a>
        </div>

        <?php if (empty($packages)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h5>No packages found</h5>
                <p class="text-muted">Create your first package!</p>
                <a href="add-package.php" class="btn btn-success">Create Package</a>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($packages as $package): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 package-card <?php echo $package['status'] === 'inactive' ? 'bg-light' : ''; ?>">
                    <div class="card-body text-center">
                        <div class="package-speed text-primary" style="font-size: 2rem; font-weight: bold;"><?php echo formatSpeed($package['download_speed']); ?></div>
                        <h5 class="card-title mt-2"><?php echo htmlspecialchars($package['name']); ?></h5>
                        <p class="package-price text-success" style="font-size: 1.5rem; font-weight: bold;"><?php echo formatCurrency($package['price']); ?></p>
                        <p class="text-muted small">per <?php echo $package['billing_cycle']; ?></p>
                        <hr>
                        <div class="row text-center small">
                            <div class="col-6"><i class="fas fa-arrow-up text-success"></i> <?php echo formatSpeed($package['upload_speed']); ?></div>
                            <div class="col-6"><i class="fas fa-database text-primary"></i> <?php echo formatBandwidth($package['bandwidth_limit']); ?></div>
                        </div>
                        <hr>
                        <div class="mt-3">
                            <span class="badge bg-<?php echo getStatusBadgeClass($package['status']); ?>"><?php echo getStatusLabel($package['status']); ?></span>
                            <span class="badge bg-info"><?php echo $package['subscriber_count']; ?> subscribers</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="edit-package.php?id=<?php echo $package['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $package['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning"><i class="fas <?php echo $package['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i></button>
                            </form>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this package?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

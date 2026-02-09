<?php
/**
 * Packages List Page
 */

$pageTitle = 'Packages - ' . APP_NAME;
require_once 'header.php';

// Handle package actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $action = $_POST['action'];
        $package_id = intval($_POST['package_id'] ?? 0);
        
        if ($action === 'update_status' && $package_id > 0) {
            $status = sanitize($_POST['status'] ?? '');
            query(
                "UPDATE packages SET status = ? WHERE id = ?",
                [$status, $package_id],
                'billing'
            );
            setFlashMessage('success', 'Package status updated successfully!');
        } elseif ($action === 'delete' && $package_id > 0) {
            // Check if package is in use
            $usage = fetch(
                "SELECT COUNT(*) as count FROM customer_packages WHERE package_id = ? AND status = 'active'",
                [$package_id],
                'billing'
            );
            if ($usage['count'] > 0) {
                setFlashMessage('error', 'Cannot delete package - it is currently assigned to customers!');
            } else {
                query("DELETE FROM packages WHERE id = ?", [$package_id], 'billing');
                setFlashMessage('success', 'Package deleted successfully!');
            }
        }
    }
    redirect('packages.php');
}

// Get all packages
$packages = fetchAll(
    "SELECT p.*, 
     (SELECT COUNT(*) FROM customer_packages WHERE package_id = p.id AND status = 'active') as subscriber_count
     FROM packages p 
     ORDER BY p.price ASC",
    [],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Package Management</h5>
                <a href="add-package.php" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Add New Package
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($packages)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p class="mb-0">No packages found. Create your first package!</p>
                    <a href="add-package.php" class="btn btn-success mt-3">
                        <i class="fas fa-plus me-1"></i>Create Package
                    </a>
                </div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($packages as $package): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 package-card <?php echo $package['status'] === 'inactive' ? 'bg-light' : ''; ?>">
                            <div class="card-body text-center">
                                <div class="package-speed"><?php echo formatSpeed($package['download_speed']); ?></div>
                                <h5 class="card-title mt-2"><?php echo htmlspecialchars($package['name']); ?></h5>
                                <p class="package-price"><?php echo formatCurrency($package['price']); ?></p>
                                <p class="text-muted small">per <?php echo $package['billing_cycle']; ?></p>
                                
                                <hr>
                                
                                <div class="row text-center small">
                                    <div class="col-6">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <?php echo formatSpeed($package['upload_speed']); ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="fas fa-database text-primary"></i>
                                        <?php echo formatBandwidth($package['bandwidth_limit']); ?>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <p class="card-text small text-muted">
                                    <?php echo htmlspecialchars(substr($package['description'] ?? '', 0, 100)); ?>
                                    <?php echo strlen($package['description'] ?? '') > 100 ? '...' : ''; ?>
                                </p>
                                
                                <div class="mt-3">
                                    <span class="badge bg-<?php echo getStatusBadgeClass($package['status']); ?>">
                                        <?php echo getStatusLabel($package['status']); ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <?php echo $package['subscriber_count']; ?> subscribers
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit-package.php?id=<?php echo $package['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Status Toggle -->
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $package['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                title="<?php echo $package['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas <?php echo $package['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Delete Button -->
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="App.deleteItem('packages.php', <?php echo $package['id']; ?>, 'Package deleted successfully!')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

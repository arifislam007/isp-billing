<?php
/**
 * Edit Package Page
 */

$pageTitle = 'Edit Package - ' . APP_NAME;
require_once 'header.php';

$package_id = intval($_GET['id'] ?? 0);

if ($package_id <= 0) {
    setFlashMessage('error', 'Invalid package ID');
    redirect('packages.php');
}

$package = fetch(
    "SELECT * FROM packages WHERE id = ?",
    [$package_id],
    'billing'
);

if (!$package) {
    setFlashMessage('error', 'Package not found');
    redirect('packages.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $download_speed = intval($_POST['download_speed'] ?? 0);
        $upload_speed = intval($_POST['upload_speed'] ?? 0);
        $bandwidth_limit = intval($_POST['bandwidth_limit'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $billing_cycle = sanitize($_POST['billing_cycle'] ?? 'monthly');
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Validation
        if (empty($name)) {
            $error = 'Please enter package name';
        } elseif ($price <= 0) {
            $error = 'Please enter a valid price';
        } else {
            // Check if name exists for other packages
            $existing = fetch(
                "SELECT id FROM packages WHERE name = ? AND id != ?",
                [$name, $package_id],
                'billing'
            );
            if ($existing) {
                $error = 'Package name already exists';
            } else {
                // Update package
                query(
                    "UPDATE packages SET name = ?, description = ?, download_speed = ?, upload_speed = ?, 
                     bandwidth_limit = ?, price = ?, billing_cycle = ?, status = ?
                     WHERE id = ?",
                    [$name, $description, $download_speed, $upload_speed, $bandwidth_limit, $price, $billing_cycle, $status, $package_id],
                    'billing'
                );
                
                setFlashMessage('success', 'Package updated successfully!');
                redirect('packages.php');
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Edit Package</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Package Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($package['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="download_speed" class="form-label">Download Speed (Kbps) *</label>
                            <input type="number" class="form-control" id="download_speed" name="download_speed" 
                                   value="<?php echo $package['download_speed']; ?>" required min="1">
                            <small class="text-muted">Enter in Kbps (5120 = 5Mbps)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="upload_speed" class="form-label">Upload Speed (Kbps) *</label>
                            <input type="number" class="form-control" id="upload_speed" name="upload_speed" 
                                   value="<?php echo $package['upload_speed']; ?>" required min="1">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bandwidth_limit" class="form-label">Bandwidth Limit (Bytes)</label>
                            <input type="number" class="form-control" id="bandwidth_limit" name="bandwidth_limit" 
                                   value="<?php echo $package['bandwidth_limit']; ?>" min="0">
                            <small class="text-muted">Enter 0 for unlimited</small>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (৳) *</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo $package['price']; ?>" required min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="billing_cycle" class="form-label">Billing Cycle *</label>
                            <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                                <option value="daily" <?php echo $package['billing_cycle'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $package['billing_cycle'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $package['billing_cycle'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="quarterly" <?php echo $package['billing_cycle'] === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                <option value="yearly" <?php echo $package['billing_cycle'] === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $package['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $package['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Update Package
                        </button>
                        <a href="packages.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

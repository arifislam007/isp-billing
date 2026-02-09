<?php
/**
 * Add New Package Page
 */

$pageTitle = 'Add New Package - ' . APP_NAME;
require_once 'header.php';

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
        
        // Validation
        if (empty($name)) {
            $error = 'Please enter package name';
        } elseif ($price <= 0) {
            $error = 'Please enter a valid price';
        } else {
            // Check if package name exists
            $existing = fetch("SELECT id FROM packages WHERE name = ?", [$name], 'billing');
            if ($existing) {
                $error = 'Package name already exists';
            } else {
                // Insert package
                query(
                    "INSERT INTO packages (name, description, download_speed, upload_speed, bandwidth_limit, price, billing_cycle, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
                    [$name, $description, $download_speed, $upload_speed, $bandwidth_limit, $price, $billing_cycle],
                    'billing'
                );
                
                setFlashMessage('success', 'Package created successfully!');
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
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Add New Package</h5>
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
                               placeholder="e.g., Basic 5Mbps, Premium 20Mbps" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Enter package description"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="download_speed" class="form-label">Download Speed (Kbps) *</label>
                            <input type="number" class="form-control" id="download_speed" name="download_speed" 
                                   placeholder="e.g., 5120 for 5Mbps" required min="1">
                            <small class="text-muted">Enter in Kbps (5120 = 5Mbps)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="upload_speed" class="form-label">Upload Speed (Kbps) *</label>
                            <input type="number" class="form-control" id="upload_speed" name="upload_speed" 
                                   placeholder="e.g., 1024 for 1Mbps" required min="1">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bandwidth_limit" class="form-label">Bandwidth Limit (Bytes)</label>
                            <input type="number" class="form-control" id="bandwidth_limit" name="bandwidth_limit" 
                                   placeholder="e.g., 50000000000 for 50GB" min="0">
                            <small class="text-muted">Enter 0 for unlimited</small>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (৳) *</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   placeholder="0.00" required min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="billing_cycle" class="form-label">Billing Cycle *</label>
                        <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Create Package
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

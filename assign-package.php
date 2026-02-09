<?php
/**
 * Assign Package to Customer Page
 */

$pageTitle = 'Assign Package - ' . APP_NAME;
require_once 'header.php';

$customer_id = intval($_GET['customer_id'] ?? 0);

if ($customer_id <= 0) {
    setFlashMessage('error', 'Invalid customer ID');
    redirect('customers.php');
}

$customer = fetch(
    "SELECT * FROM customers WHERE id = ?",
    [$customer_id],
    'billing'
);

if (!$customer) {
    setFlashMessage('error', 'Customer not found');
    redirect('customers.php');
}

// Get active packages
$packages = fetchAll(
    "SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC",
    [],
    'billing'
);

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $package_id = intval($_POST['package_id'] ?? 0);
        $start_date = sanitize($_POST['start_date'] ?? '');
        
        if ($package_id <= 0) {
            $error = 'Please select a package';
        } elseif (empty($start_date)) {
            $error = 'Please select a start date';
        } else {
            $package = fetch("SELECT * FROM packages WHERE id = ?", [$package_id], 'billing');
            
            if (!$package) {
                $error = 'Package not found';
            } else {
                // Calculate end date
                $end_date = calculateEndDate($start_date, $package['billing_cycle']);
                
                // Deactivate existing packages
                query(
                    "UPDATE customer_packages SET status = 'cancelled' WHERE customer_id = ? AND status = 'active'",
                    [$customer_id],
                    'billing'
                );
                
                // Assign new package
                query(
                    "INSERT INTO customer_packages (customer_id, package_id, start_date, end_date, status)
                     VALUES (?, ?, ?, ?, 'active')",
                    [$customer_id, $package_id, $start_date, $end_date],
                    'billing'
                );
                
                // Update RADIUS group reply for speed limits
                $username = $customer['username'];
                
                // Remove existing speed attributes
                query(
                    "DELETE FROM radreply WHERE username = ? AND attribute IN ('WISPr-Bandwidth-Max-Down', 'WISPr-Bandwidth-Max-Up', 'Framed-Pool')",
                    [$username],
                    'radius'
                );
                
                // Add new speed attributes
                query(
                    "INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'WISPr-Bandwidth-Max-Down', ':=', ?)",
                    [$username, $package['download_speed'] * 1000],
                    'radius'
                );
                
                query(
                    "INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'WISPr-Bandwidth-Max-Up', ':=', ?)",
                    [$username, $package['upload_speed'] * 1000],
                    'radius'
                );
                
                setFlashMessage('success', 'Package assigned successfully!');
                redirect('customer-view.php?id=' . $customer_id);
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Assign Package to Customer</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Customer Info -->
                <div class="alert alert-info mb-4">
                    <strong>Customer:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                    <br>
                    <strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?>
                    <br>
                    <strong>Current Status:</strong> 
                    <span class="badge bg-<?php echo getStatusBadgeClass($customer['status']); ?>">
                        <?php echo getStatusLabel($customer['status']); ?>
                    </span>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="package_id" class="form-label">Select Package *</label>
                        <select class="form-select" id="package_id" name="package_id" required onchange="updatePackageInfo(this.value)">
                            <option value="">-- Select a Package --</option>
                            <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($pkg['name']); ?>"
                                    data-speed="<?php echo formatSpeed($pkg['download_speed']); ?>"
                                    data-price="<?php echo formatCurrency($pkg['price']); ?>"
                                    data-cycle="<?php echo htmlspecialchars($pkg['billing_cycle']); ?>">
                                <?php echo htmlspecialchars($pkg['name']); ?> - <?php echo formatCurrency($pkg['price']); ?>/<?php echo $pkg['billing_cycle']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Package Info Display -->
                    <div id="packageInfo" class="card mb-3" style="display: none;">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <i class="fas fa-tachometer-alt text-primary fa-2x mb-2"></i>
                                    <h5 id="pkgSpeed">0 Mbps</h5>
                                    <small class="text-muted">Speed</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-tag text-success fa-2x mb-2"></i>
                                    <h5 id="pkgPrice">৳ 0.00</h5>
                                    <small class="text-muted">Price</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-redo text-warning fa-2x mb-2"></i>
                                    <h5 id="pkgCycle">Monthly</h5>
                                    <small class="text-muted">Billing Cycle</small>
                                </div>
                            </div>
                            <hr>
                            <p class="mb-0" id="pkgDescription"></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Assign Package
                        </button>
                        <a href="customer-view.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updatePackageInfo(packageId) {
    const select = document.getElementById('package_id');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('packageInfo');
    
    if (packageId && option) {
        document.getElementById('pkgSpeed').textContent = option.dataset.speed || 'N/A';
        document.getElementById('pkgPrice').textContent = option.dataset.price || 'N/A';
        document.getElementById('pkgCycle').textContent = option.dataset.cycle || 'N/A';
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>

<?php require_once 'footer.php'; ?>

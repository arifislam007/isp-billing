<?php
/**
 * Add New NAS Page
 */

$pageTitle = 'Add New NAS - ' . APP_NAME;
require_once 'header.php';

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $nasname = sanitize($_POST['nasname'] ?? '');
        $shortname = sanitize($_POST['shortname'] ?? '');
        $type = sanitize($_POST['type'] ?? 'other');
        $ports = intval($_POST['ports'] ?? 0);
        $secret = sanitize($_POST['secret'] ?? '');
        $server = sanitize($_POST['server'] ?? '');
        $community = sanitize($_POST['community'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        // Validation
        if (empty($nasname)) {
            $error = 'Please enter NAS IP/Hostname';
        } elseif (empty($secret)) {
            $error = 'Please enter RADIUS secret';
        } else {
            // Insert NAS
            query(
                "INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                [$nasname, $shortname, $type, $ports, $secret, $server, $community, $description],
                'billing'
            );
            
            $nasId = lastInsertId('billing');
            
            // Also add to FreeRADIUS database
            query(
                "INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$nasname, $shortname, $type, $ports, $secret, $server, $community, $description],
                'radius'
            );
            
            setFlashMessage('success', 'NAS added successfully!');
            redirect('nas.php');
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>Add New NAS</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nasname" class="form-label">NAS IP/Hostname *</label>
                            <input type="text" class="form-control" id="nasname" name="nasname" 
                                   placeholder="e.g., 192.168.1.1 or router1.isp.com" required>
                            <small class="text-muted">IP address or hostname of the NAS device</small>
                        </div>
                        <div class="col-md-6">
                            <label for="shortname" class="form-label">Short Name</label>
                            <input type="text" class="form-control" id="shortname" name="shortname" 
                                   placeholder="e.g., Router1">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">NAS Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="other">Other</option>
                                <option value="cisco">Cisco</option>
                                <option value="juniper">Juniper</option>
                                <option value="mikrotik">MikroTik</option>
                                <option value="ubiquiti">Ubiquiti</option>
                                <option value="huawei">Huawei</option>
                                <option value="zte">ZTE</option>
                                <option value="d-link">D-Link</option>
                                <option value="tp-link">TP-Link</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ports" class="form-label">Number of Ports</label>
                            <input type="number" class="form-control" id="ports" name="ports" 
                                   placeholder="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="secret" class="form-label">RADIUS Secret *</label>
                            <input type="text" class="form-control" id="secret" name="secret" 
                                   placeholder="Shared secret" required>
                            <small class="text-muted">Must match NAS configuration</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="server" class="form-label">Authentication Server</label>
                            <input type="text" class="form-control" id="server" name="server" 
                                   placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label for="community" class="form-label">SNMP Community</label>
                            <input type="text" class="form-control" id="community" name="community" 
                                   placeholder="Optional">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Enter description or notes"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save me-2"></i>Add NAS
                        </button>
                        <a href="nas.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

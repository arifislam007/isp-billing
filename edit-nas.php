<?php
/**
 * Edit NAS Page
 */

$pageTitle = 'Edit NAS - ' . APP_NAME;
require_once 'header.php';

$nas_id = intval($_GET['id'] ?? 0);

if ($nas_id <= 0) {
    setFlashMessage('error', 'Invalid NAS ID');
    redirect('nas.php');
}

$nas = fetch(
    "SELECT * FROM nas WHERE id = ?",
    [$nas_id],
    'billing'
);

if (!$nas) {
    setFlashMessage('error', 'NAS not found');
    redirect('nas.php');
}

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
        $status = sanitize($_POST['status'] ?? 'active');
        
        if (empty($nasname)) {
            $error = 'Please enter NAS IP/Hostname';
        } elseif (empty($secret)) {
            $error = 'Please enter RADIUS secret';
        } else {
            // Update NAS
            query(
                "UPDATE nas SET nasname = ?, shortname = ?, type = ?, ports = ?, secret = ?, 
                 server = ?, community = ?, description = ?, status = ?
                 WHERE id = ?",
                [$nasname, $shortname, $type, $ports, $secret, $server, $community, $description, $status, $nas_id],
                'billing'
            );
            
            // Update in RADIUS database
            query(
                "UPDATE nas SET nasname = ?, shortname = ?, type = ?, ports = ?, secret = ?, 
                 server = ?, community = ?, description = ?
                 WHERE nasname = ?",
                [$nasname, $shortname, $type, $ports, $secret, $server, $community, $description, $nas['nasname']],
                'radius'
            );
            
            setFlashMessage('success', 'NAS updated successfully!');
            redirect('nas.php');
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>Edit NAS</h5>
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
                                   value="<?php echo htmlspecialchars($nas['nasname']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="shortname" class="form-label">Short Name</label>
                            <input type="text" class="form-control" id="shortname" name="shortname" 
                                   value="<?php echo htmlspecialchars($nas['shortname'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">NAS Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="other" <?php echo $nas['type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="cisco" <?php echo $nas['type'] === 'cisco' ? 'selected' : ''; ?>>Cisco</option>
                                <option value="juniper" <?php echo $nas['type'] === 'juniper' ? 'selected' : ''; ?>>Juniper</option>
                                <option value="mikrotik" <?php echo $nas['type'] === 'mikrotik' ? 'selected' : ''; ?>>MikroTik</option>
                                <option value="ubiquiti" <?php echo $nas['type'] === 'ubiquiti' ? 'selected' : ''; ?>>Ubiquiti</option>
                                <option value="huawei" <?php echo $nas['type'] === 'huawei' ? 'selected' : ''; ?>>Huawei</option>
                                <option value="zte" <?php echo $nas['type'] === 'zte' ? 'selected' : ''; ?>>ZTE</option>
                                <option value="d-link" <?php echo $nas['type'] === 'd-link' ? 'selected' : ''; ?>>D-Link</option>
                                <option value="tp-link" <?php echo $nas['type'] === 'tp-link' ? 'selected' : ''; ?>>TP-Link</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ports" class="form-label">Number of Ports</label>
                            <input type="number" class="form-control" id="ports" name="ports" 
                                   value="<?php echo $nas['ports']; ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="secret" class="form-label">RADIUS Secret *</label>
                            <input type="text" class="form-control" id="secret" name="secret" 
                                   value="<?php echo htmlspecialchars($nas['secret']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="server" class="form-label">Authentication Server</label>
                            <input type="text" class="form-control" id="server" name="server" 
                                   value="<?php echo htmlspecialchars($nas['server'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="community" class="form-label">SNMP Community</label>
                            <input type="text" class="form-control" id="community" name="community" 
                                   value="<?php echo htmlspecialchars($nas['community'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($nas['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $nas['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $nas['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save me-2"></i>Update NAS
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

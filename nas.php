<?php
/**
 * NAS List Page
 */

$pageTitle = 'NAS Management - ' . APP_NAME;
require_once 'header.php';

// Handle NAS actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $action = $_POST['action'];
        $nas_id = intval($_POST['nas_id'] ?? 0);
        
        if ($action === 'update_status' && $nas_id > 0) {
            $status = sanitize($_POST['status'] ?? '');
            query(
                "UPDATE nas SET status = ? WHERE id = ?",
                [$status, $nas_id],
                'billing'
            );
            setFlashMessage('success', 'NAS status updated successfully!');
        } elseif ($action === 'delete' && $nas_id > 0) {
            // Get nasname for RADIUS deletion
            $nas = fetch("SELECT nasname FROM nas WHERE id = ?", [$nas_id], 'billing');
            if ($nas) {
                query("DELETE FROM nas WHERE nasname = ?", [$nas['nasname']], 'radius');
            }
            query("DELETE FROM nas WHERE id = ?", [$nas_id], 'billing');
            setFlashMessage('success', 'NAS deleted successfully!');
        }
    }
    redirect('nas.php');
}

// Get all NAS devices
$nasList = fetchAll(
    "SELECT * FROM nas ORDER BY created_at DESC",
    [],
    'billing'
);

// Count active NAS
$activeCount = fetch(
    "SELECT COUNT(*) as count FROM nas WHERE status = 'active'",
    [],
    'billing'
)['count'];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>NAS Management</h5>
                <a href="add-nas.php" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Add New NAS
                </a>
            </div>
            <div class="card-body">
                <!-- Summary -->
                <div class="alert alert-info mb-4">
                    <strong>Active NAS Devices:</strong> <?php echo $activeCount; ?> | 
                    <strong>Total NAS:</strong> <?php echo count($nasList); ?>
                </div>
                
                <?php if (empty($nasList)): ?>
                <div class="empty-state">
                    <i class="fas fa-server"></i>
                    <p class="mb-0">No NAS devices found. Add your first NAS device!</p>
                    <a href="add-nas.php" class="btn btn-info mt-3">
                        <i class="fas fa-plus me-1"></i>Add NAS
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="nasTable">
                        <thead>
                            <tr>
                                <th>NAS Name/IP</th>
                                <th>Short Name</th>
                                <th>Type</th>
                                <th>Ports</th>
                                <th>Secret</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nasList as $nas): ?>
                            <tr data-id="<?php echo $nas['id']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($nas['nasname']); ?></strong>
                                    <?php if (!empty($nas['description'])): ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($nas['description'], 0, 50)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($nas['shortname'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars(ucfirst($nas['type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($nas['ports']); ?></td>
                                <td>
                                    <code><?php echo htmlspecialchars(substr($nas['secret'], 0, 8)); ?>...</code>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($nas['status']); ?>">
                                        <?php echo getStatusLabel($nas['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit-nas.php?id=<?php echo $nas['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Status Toggle -->
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="nas_id" value="<?php echo $nas['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $nas['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-outline-warning" 
                                                    title="<?php echo $nas['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $nas['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="App.deleteItem('nas.php', <?php echo $nas['id']; ?>, 'NAS deleted successfully!')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
</div>

<?php require_once 'footer.php'; ?>

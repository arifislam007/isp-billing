<?php
/**
 * Admin Users List Page
 */

$pageTitle = 'Admin Users - ' . APP_NAME;
require_once 'header.php';

// Only admins can manage admin users
if (!hasRole('admin')) {
    setFlashMessage('error', 'Access denied');
    redirect('dashboard.php');
}

// Handle admin user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (validateCSRFToken($csrf_token)) {
        $action = $_POST['action'];
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($action === 'update_status' && $user_id > 0) {
            $status = sanitize($_POST['status'] ?? '');
            query(
                "UPDATE admin_users SET status = ? WHERE id = ?",
                [$status, $user_id],
                'billing'
            );
            setFlashMessage('success', 'User status updated!');
        } elseif ($action === 'delete' && $user_id > 0) {
            // Prevent deleting yourself
            if ($user_id == Auth::getUserId()) {
                setFlashMessage('error', 'You cannot delete your own account!');
            } else {
                query("DELETE FROM admin_users WHERE id = ?", [$user_id], 'billing');
                setFlashMessage('success', 'User deleted!');
            }
        }
    }
    redirect('admin-users.php');
}

// Get all admin users
$adminUsers = fetchAll(
    "SELECT * FROM admin_users ORDER BY created_at DESC",
    [],
    'billing'
);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin User Management</h5>
                <a href="add-admin.php" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Add New Admin
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($adminUsers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p class="mb-0">No admin users found.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $user): ?>
                            <tr data-id="<?php echo $user['id']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($user['status']); ?>">
                                        <?php echo getStatusLabel($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit-admin.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != Auth::getUserId()): ?>
                                        <!-- Status Toggle -->
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-outline-warning" 
                                                    title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $user['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="App.deleteItem('admin-users.php', <?php echo $user['id']; ?>, 'User deleted!')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="badge bg-secondary align-self-center">You</span>
                                        <?php endif; ?>
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

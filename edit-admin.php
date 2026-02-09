<?php
/**
 * Edit Admin User Page
 */

$pageTitle = 'Edit Admin User - ' . APP_NAME;
require_once 'header.php';

// Only admins can edit admin users
if (!hasRole('admin')) {
    setFlashMessage('error', 'Access denied');
    redirect('dashboard.php');
}

$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    setFlashMessage('error', 'Invalid user ID');
    redirect('admin-users.php');
}

$adminUser = fetch(
    "SELECT * FROM admin_users WHERE id = ?",
    [$user_id],
    'billing'
);

if (!$adminUser) {
    setFlashMessage('error', 'User not found');
    redirect('admin-users.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $role = sanitize($_POST['role'] ?? 'support');
        $status = sanitize($_POST['status'] ?? 'active');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($email) || empty($full_name)) {
            $error = 'Please fill in all required fields';
        } elseif (!empty($new_password) && strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Update user
            $params = [$email, $full_name, $role, $status];
            $sql = "UPDATE admin_users SET email = ?, full_name = ?, role = ?, status = ?";
            
            if (!empty($new_password)) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashedPassword;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            query($sql, $params, 'billing');
            
            setFlashMessage('success', 'Admin user updated successfully!');
            redirect('admin-users.php');
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Admin User</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($adminUser['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($adminUser['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($adminUser['full_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="support" <?php echo $adminUser['role'] === 'support' ? 'selected' : ''; ?>>Support</option>
                                <option value="manager" <?php echo $adminUser['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                <option value="admin" <?php echo $adminUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $adminUser['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $adminUser['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    <h5>Change Password</h5>
                    <p class="text-muted small mb-3">Leave blank to keep current password</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Admin User
                        </button>
                        <a href="admin-users.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

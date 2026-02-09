<?php
/**
 * Profile Page
 */

$pageTitle = 'My Profile - ' . APP_NAME;
require_once 'header.php';

$user = getCurrentUser();

$adminUser = fetch(
    "SELECT * FROM admin_users WHERE id = ?",
    [Auth::getUserId()],
    'billing'
);

if (!$adminUser) {
    setFlashMessage('error', 'User not found');
    redirect('login.php');
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        
        if (empty($email) || empty($full_name)) {
            $error = 'Please fill in all required fields';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address';
        } else {
            query(
                "UPDATE admin_users SET email = ?, full_name = ? WHERE id = ?",
                [$email, $full_name, Auth::getUserId()],
                'billing'
            );
            
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_full_name'] = $full_name;
            
            $success = 'Profile updated successfully!';
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>My Profile</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($adminUser['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($adminUser['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($adminUser['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($adminUser['role']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <span class="badge bg-<?php echo getStatusBadgeClass($adminUser['status']); ?>">
                            <?php echo getStatusLabel($adminUser['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?php echo formatDate($adminUser['created_at']); ?>" disabled>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                        <a href="change-password.php" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>User ID:</strong> <?php echo $adminUser['id']; ?></p>
                        <p class="mb-2"><strong>Role:</strong> 
                            <span class="badge bg-<?php echo $adminUser['role'] === 'admin' ? 'danger' : ($adminUser['role'] === 'manager' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($adminUser['role']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Last Updated:</strong> <?php echo formatDate($adminUser['updated_at']); ?></p>
                        <p class="mb-2"><strong>Status:</strong> 
                            <span class="badge bg-<?php echo getStatusBadgeClass($adminUser['status']); ?>">
                                <?php echo getStatusLabel($adminUser['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

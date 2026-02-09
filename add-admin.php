<?php
/**
 * Add New Admin User Page
 */

$pageTitle = 'Add New Admin - ' . APP_NAME;
require_once 'header.php';

// Only admins can add admin users
if (!hasRole('admin')) {
    setFlashMessage('error', 'Access denied');
    redirect('dashboard.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $role = sanitize($_POST['role'] ?? 'support');
        
        if (empty($username) || empty($password) || empty($email) || empty($full_name)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address';
        } else {
            $existing = fetch("SELECT id FROM admin_users WHERE username = ?", [$username], 'billing');
            if ($existing) {
                $error = 'Username already exists';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                query(
                    "INSERT INTO admin_users (username, password, email, full_name, role, status)
                     VALUES (?, ?, ?, ?, ?, 'active')",
                    [$username, $hashedPassword, $email, $full_name, $role],
                    'billing'
                );
                
                setFlashMessage('success', 'Admin user created successfully!');
                redirect('admin-users.php');
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Admin User</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Enter username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter email" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter password" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm password" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="support">Support</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                            <small class="text-muted">Admins have full access</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Admin User
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

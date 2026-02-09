<?php
/**
 * Edit Customer Page
 */

$pageTitle = 'Edit Customer - ' . APP_NAME;
require_once 'header.php';

$customer_id = intval($_GET['id'] ?? 0);

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

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $nid_number = sanitize($_POST['nid_number'] ?? '');
        $status = sanitize($_POST['status'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address';
        } elseif (!empty($new_password) && strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Check if email exists for other users
            $existingEmail = fetch(
                "SELECT id FROM customers WHERE email = ? AND id != ?",
                [$email, $customer_id],
                'billing'
            );
            if ($existingEmail) {
                $error = 'Email already exists for another customer';
            } else {
                // Update customer
                $params = [$first_name, $last_name, $email, $phone, $address, $nid_number, $status];
                $sql = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, nid_number = ?, status = ?";
                
                // Update password if provided
                if (!empty($new_password)) {
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $hashedPassword;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $customer_id;
                
                query($sql, $params, 'billing');
                
                // Update RADIUS password if changed
                if (!empty($new_password)) {
                    query(
                        "UPDATE radcheck SET value = ? WHERE username = ? AND attribute = 'Cleartext-Password'",
                        [$new_password, $customer['username']],
                        'radius'
                    );
                }
                
                setFlashMessage('success', 'Customer updated successfully!');
                redirect('customer-view.php?id=' . $customer_id);
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Customer</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($customer['phone']); ?>" required onkeyup="formatPhoneNumber(this)">
                        </div>
                        <div class="col-md-6">
                            <label for="nid_number" class="form-label">NID Number</label>
                            <input type="text" class="form-control" id="nid_number" name="nid_number" 
                                   value="<?php echo htmlspecialchars($customer['nid_number']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $customer['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="disconnected" <?php echo $customer['status'] === 'disconnected' ? 'selected' : ''; ?>>Disconnected</option>
                        </select>
                    </div>
                    
                    <hr>
                    <h5 class="mb-3">Change Password</h5>
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
                            <i class="fas fa-save me-2"></i>Update Customer
                        </button>
                        <a href="customer-view.php?id=<?php echo $customer['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

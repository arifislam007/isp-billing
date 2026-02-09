<?php
/**
 * Add New Customer Page
 */

$pageTitle = 'Add New Customer - ' . APP_NAME;
require_once 'header.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitize($_POST['csrf_token'] ?? '');
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid form submission';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $nid_number = sanitize($_POST['nid_number'] ?? '');
        
        // Validation
        if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if username exists
            $existing = fetch("SELECT id FROM customers WHERE username = ?", [$username], 'billing');
            if ($existing) {
                $error = 'Username already exists';
            } else {
                // Check if email exists
                $existingEmail = fetch("SELECT id FROM customers WHERE email = ?", [$email], 'billing');
                if ($existingEmail) {
                    $error = 'Email already exists';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert customer
                    query(
                        "INSERT INTO customers (username, password, first_name, last_name, email, phone, address, nid_number, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                        [$username, $hashedPassword, $first_name, $last_name, $email, $phone, $address, $nid_number],
                        'billing'
                    );
                    
                    $customerId = lastInsertId('billing');
                    
                    // Also add to FreeRADIUS database
                    query(
                        "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)",
                        [$username, $password],
                        'radius'
                    );
                    
                    setFlashMessage('success', 'Customer added successfully!');
                    redirect('customers.php');
                }
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5>
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
                            <small class="text-muted">Unique identifier for customer login</small>
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
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="Enter first name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Enter last name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="Enter phone number" required onkeyup="formatPhoneNumber(this)">
                        </div>
                        <div class="col-md-6">
                            <label for="nid_number" class="form-label">NID Number</label>
                            <input type="text" class="form-control" id="nid_number" name="nid_number" 
                                   placeholder="National ID Number">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" 
                                  placeholder="Enter full address"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Customer
                        </button>
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

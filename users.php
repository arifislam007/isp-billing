<?php
require_once 'db.php';

$db = new RadDB();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $username = trim($_POST['username'] ?? '');
                $attribute = $_POST['attribute'] ?? 'Cleartext-Password';
                $op = $_POST['op'] ?? ':=';
                $value = $_POST['value'] ?? '';
                
                if ($username) {
                    if ($db->addUser($username, $attribute, $op, $value)) {
                        $message = "User '$username' created successfully!";
                        $messageType = 'success';
                    }
                } else {
                    $message = "Username is required!";
                    $messageType = 'error';
                }
                break;
                
            case 'add_check':
                $username = $_POST['username'] ?? '';
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? ':=';
                $value = $_POST['value'] ?? '';
                
                if ($username && $attribute) {
                    $db->addUserCheckAttribute($username, $attribute, $op, $value);
                    $message = "Check attribute added!";
                    $messageType = 'success';
                }
                break;
                
            case 'add_reply':
                $username = $_POST['username'] ?? '';
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? '=';
                $value = $_POST['value'] ?? '';
                
                if ($username && $attribute) {
                    $db->addUserReplyAttribute($username, $attribute, $op, $value);
                    $message = "Reply attribute added!";
                    $messageType = 'success';
                }
                break;
                
            case 'update_check':
                $id = $_POST['id'] ?? 0;
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? ':=';
                $value = $_POST['value'] ?? '';
                
                if ($id && $attribute) {
                    $db->updateCheckAttribute($id, $attribute, $op, $value);
                    $message = "Check attribute updated!";
                    $messageType = 'success';
                }
                break;
                
            case 'update_reply':
                $id = $_POST['id'] ?? 0;
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? '=';
                $value = $_POST['value'] ?? '';
                
                if ($id && $attribute) {
                    $db->updateReplyAttribute($id, $attribute, $op, $value);
                    $message = "Reply attribute updated!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete_check':
                $id = $_POST['id'] ?? 0;
                $username = $_POST['username'] ?? '';
                if ($id) {
                    $db->deleteCheckAttribute($id);
                    $message = "Check attribute deleted!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete_reply':
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $db->deleteReplyAttribute($id);
                    $message = "Reply attribute deleted!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                $username = $_POST['username'] ?? '';
                if ($username) {
                    $db->deleteUser($username);
                    $message = "User '$username' deleted!";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all users
$users = $db->getAllUsers();
$selectedUser = $_GET['user'] ?? '';
$userCheckAttrs = [];
$userReplyAttrs = [];
$userGroups = [];

if ($selectedUser) {
    $userCheckAttrs = $db->getUserCheckAttributes($selectedUser);
    $userReplyAttrs = $db->getUserReplyAttributes($selectedUser);
    $userGroups = $db->getUserGroups($selectedUser);
}

// Get available groups for adding user to group
$availableGroups = $db->getGroupsForSelect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - FreeRADIUS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">FreeRADIUS Manager</div>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="users.php" class="active">Users</a></li>
            <li><a href="groups.php">Groups</a></li>
            <li><a href="nas.php">NAS Clients</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>User Management</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="page-grid">
            <!-- User List -->
            <div class="card">
                <h2>Users</h2>
                <form method="GET" class="search-form">
                    <input type="text" name="user" placeholder="Search username..." value="<?php echo htmlspecialchars($selectedUser); ?>">
                    <button type="submit">Search</button>
                </form>
                
                <?php if ($selectedUser): ?>
                    <div class="user-selected">
                        <p><strong>Selected:</strong> <?php echo htmlspecialchars($selectedUser); ?></p>
                        <a href="users.php" class="btn btn-secondary">Clear Selection</a>
                    </div>
                <?php endif; ?>
                
                <div class="user-list">
                    <?php foreach ($users as $user): ?>
                        <a href="?user=<?php echo urlencode($user['username']); ?>" 
                           class="user-item <?php echo $selectedUser === $user['username'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                        <p class="empty-state">No users found. Add a user below.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Details -->
            <div class="card">
                <?php if ($selectedUser): ?>
                    <div class="user-header">
                        <h2><?php echo htmlspecialchars($selectedUser); ?></h2>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($selectedUser); ?>">
                            <button type="submit" class="btn btn-danger">Delete User</button>
                        </form>
                    </div>
                    
                    <!-- Groups -->
                    <div class="section">
                        <h3>Groups</h3>
                        <?php if ($userGroups): ?>
                            <ul class="group-list">
                                <?php foreach ($userGroups as $group): ?>
                                    <li>
                                        <?php echo htmlspecialchars($group['groupname']); ?>
                                        <span class="priority">(Priority: <?php echo $group['priority']; ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-state">User is not in any group.</p>
                        <?php endif; ?>
                        
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="add_user_to_group">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($selectedUser); ?>">
                            <select name="groupname" required>
                                <option value="">Select group...</option>
                                <?php foreach ($availableGroups as $group): ?>
                                    <option value="<?php echo htmlspecialchars($group['groupname']); ?>">
                                        <?php echo htmlspecialchars($group['groupname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="priority" value="1" min="0" max="10" style="width: 60px;">
                            <button type="submit" class="btn btn-small">Add to Group</button>
                        </form>
                    </div>

                    <!-- Check Attributes -->
                    <div class="section">
                        <h3>Check Attributes (radcheck)</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Attribute</th>
                                    <th>Op</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userCheckAttrs as $attr): ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_check">
                                            <input type="hidden" name="id" value="<?php echo $attr['id']; ?>">
                                            <td>
                                                <select name="attribute">
                                                    <option value="Cleartext-Password" <?php echo $attr['attribute'] === 'Cleartext-Password' ? 'selected' : ''; ?>>Cleartext-Password</option>
                                                    <option value="MD5-Password" <?php echo $attr['attribute'] === 'MD5-Password' ? 'selected' : ''; ?>>MD5-Password</option>
                                                    <option value="SHA1-Password" <?php echo $attr['attribute'] === 'SHA1-Password' ? 'selected' : ''; ?>>SHA1-Password</option>
                                                    <option value="NT-Password" <?php echo $attr['attribute'] === 'NT-Password' ? 'selected' : ''; ?>>NT-Password</option>
                                                    <option value="Expiration" <?php echo $attr['attribute'] === 'Expiration' ? 'selected' : ''; ?>>Expiration</option>
                                                    <option value="Max-Daily-Session" <?php echo $attr['attribute'] === 'Max-Daily-Session' ? 'selected' : ''; ?>>Max-Daily-Session</option>
                                                    <option value="Max-Monthly-Session" <?php echo $attr['attribute'] === 'Max-Monthly-Session' ? 'selected' : ''; ?>>Max-Monthly-Session</option>
                                                    <option value="Simultaneous-Use" <?php echo $attr['attribute'] === 'Simultaneous-Use' ? 'selected' : ''; ?>>Simultaneous-Use</option>
                                                    <option value="Framed-IP-Address" <?php echo $attr['attribute'] === 'Framed-IP-Address' ? 'selected' : ''; ?>>Framed-IP-Address</option>
                                                    <option value="Pool-Name" <?php echo $attr['attribute'] === 'Pool-Name' ? 'selected' : ''; ?>>Pool-Name</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="op">
                                                    <option value="=" <?php echo $attr['op'] === '=' ? 'selected' : ''; ?>>=</option>
                                                    <option value=":=" <?php echo $attr['op'] === ':=' ? 'selected' : ''; ?>>:=</option>
                                                    <option value="!=" <?php echo $attr['op'] === '!=' ? 'selected' : ''; ?>>!=</option>
                                                    <option value=">=" <?php echo $attr['op'] === '>=' ? 'selected' : ''; ?>>>=</option>
                                                    <option value="<=" <?php echo $attr['op'] === '<=' ? 'selected' : ''; ?>>⇐</option>
                                                    <option value="=~" <?php echo $attr['op'] === '=~' ? 'selected' : ''; ?>>=~</option>
                                                    <option value="!~" <?php echo $attr['op'] === '!~' ? 'selected' : ''; ?>>!~</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="value" value="<?php echo htmlspecialchars($attr['value']); ?>"></td>
                                            <td class="actions">
                                                <button type="submit" class="btn btn-small btn-primary">Update</button>
                                            </td>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Delete this attribute?');" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_check">
                                            <input type="hidden" name="id" value="<?php echo $attr['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                        </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <form method="POST" class="add-form">
                            <input type="hidden" name="action" value="add_check">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($selectedUser); ?>">
                            <h4>Add Check Attribute</h4>
                            <div class="form-row">
                                <select name="attribute" required>
                                    <option value="Cleartext-Password">Cleartext-Password</option>
                                    <option value="MD5-Password">MD5-Password</option>
                                    <option value="SHA1-Password">SHA1-Password</option>
                                    <option value="Expiration">Expiration</option>
                                    <option value="Max-Daily-Session">Max-Daily-Session</option>
                                    <option value="Max-Monthly-Session">Max-Monthly-Session</option>
                                    <option value="Simultaneous-Use">Simultaneous-Use</option>
                                    <option value="Framed-IP-Address">Framed-IP-Address</option>
                                    <option value="Pool-Name">Pool-Name</option>
                                </select>
                                <select name="op">
                                    <option value=":=">:=</option>
                                    <option value="=">=</option>
                                    <option value="!=">!=</option>
                                </select>
                                <input type="text" name="value" placeholder="Value" required>
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                        </form>
                    </div>

                    <!-- Reply Attributes -->
                    <div class="section">
                        <h3>Reply Attributes (radreply)</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Attribute</th>
                                    <th>Op</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userReplyAttrs as $attr): ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_reply">
                                            <input type="hidden" name="id" value="<?php echo $attr['id']; ?>">
                                            <td>
                                                <select name="attribute">
                                                    <option value="Framed-Protocol" <?php echo $attr['attribute'] === 'Framed-Protocol' ? 'selected' : ''; ?>>Framed-Protocol</option>
                                                    <option value="Framed-IP-Address" <?php echo $attr['attribute'] === 'Framed-IP-Address' ? 'selected' : ''; ?>>Framed-IP-Address</option>
                                                    <option value="Framed-IP-Netmask" <?php echo $attr['attribute'] === 'Framed-IP-Netmask' ? 'selected' : ''; ?>>Framed-IP-Netmask</option>
                                                    <option value="Framed-Route" <?php echo $attr['attribute'] === 'Framed-Route' ? 'selected' : ''; ?>>Framed-Route</option>
                                                    <option value="Framed-Compression" <?php echo $attr['attribute'] === 'Framed-Compression' ? 'selected' : ''; ?>>Framed-Compression</option>
                                                    <option value="Session-Timeout" <?php echo $attr['attribute'] === 'Session-Timeout' ? 'selected' : ''; ?>>Session-Timeout</option>
                                                    <option value="Idle-Timeout" <?php echo $attr['attribute'] === 'Idle-Timeout' ? 'selected' : ''; ?>>Idle-Timeout</option>
                                                    <option value="Termination-Action" <?php echo $attr['attribute'] === 'Termination-Action' ? 'selected' : ''; ?>>Termination-Action</option>
                                                    <option value="Service-Type" <?php echo $attr['attribute'] === 'Service-Type' ? 'selected' : ''; ?>>Service-Type</option>
                                                    <option value="Acct-Interim-Interval" <?php echo $attr['attribute'] === 'Acct-Interim-Interval' ? 'selected' : ''; ?>>Acct-Interim-Interval</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="op">
                                                    <option value="=" <?php echo $attr['op'] === '=' ? 'selected' : ''; ?>>=</option>
                                                    <option value=":=" <?php echo $attr['op'] === ':=' ? 'selected' : ''; ?>>:=</option>
                                                    <option value="+=" <?php echo $attr['op'] === '+=' ? 'selected' : ''; ?>>+=</option>
                                                    <option value="!=" <?php echo $attr['op'] === '!=' ? 'selected' : ''; ?>>!=</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="value" value="<?php echo htmlspecialchars($attr['value']); ?>"></td>
                                            <td class="actions">
                                                <button type="submit" class="btn btn-small btn-primary">Update</button>
                                                <form method="POST" onsubmit="return confirm('Delete this attribute?');" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_reply">
                                                    <input type="hidden" name="id" value="<?php echo $attr['id']; ?>">
                                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <form method="POST" class="add-form">
                            <input type="hidden" name="action" value="add_reply">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($selectedUser); ?>">
                            <h4>Add Reply Attribute</h4>
                            <div class="form-row">
                                <select name="attribute" required>
                                    <option value="Framed-Protocol">Framed-Protocol</option>
                                    <option value="Framed-IP-Address">Framed-IP-Address</option>
                                    <option value="Framed-IP-Netmask">Framed-IP-Netmask</option>
                                    <option value="Framed-Route">Framed-Route</option>
                                    <option value="Framed-Compression">Framed-Compression</option>
                                    <option value="Session-Timeout">Session-Timeout</option>
                                    <option value="Idle-Timeout">Idle-Timeout</option>
                                    <option value="Termination-Action">Termination-Action</option>
                                    <option value="Service-Type">Service-Type</option>
                                    <option value="Acct-Interim-Interval">Acct-Interim-Interval</option>
                                </select>
                                <select name="op">
                                    <option value="=">=</option>
                                    <option value=":=">:=</option>
                                    <option value="+=">+=</option>
                                </select>
                                <input type="text" name="value" placeholder="Value" required>
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>Add New User</h2>
                        <form method="POST" class="add-form">
                            <input type="hidden" name="action" value="add">
                            <div class="form-row">
                                <input type="text" name="username" placeholder="Username" required>
                                <select name="attribute">
                                    <option value="Cleartext-Password">Cleartext-Password</option>
                                    <option value="MD5-Password">MD5-Password</option>
                                    <option value="SHA1-Password">SHA1-Password</option>
                                </select>
                                <select name="op">
                                    <option value=":=">:=</option>
                                    <option value="=">=</option>
                                </select>
                                <input type="text" name="value" placeholder="Password" required>
                                <button type="submit" class="btn btn-primary">Create User</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

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
                $groupname = trim($_POST['groupname'] ?? '');
                
                if ($groupname) {
                    if ($db->addGroup($groupname)) {
                        $message = "Group '$groupname' created successfully!";
                        $messageType = 'success';
                    }
                } else {
                    $message = "Group name is required!";
                    $messageType = 'error';
                }
                break;
                
            case 'add_check':
                $groupname = $_POST['groupname'] ?? '';
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? ':=';
                $value = $_POST['value'] ?? '';
                
                if ($groupname && $attribute) {
                    $db->addGroupCheckAttribute($groupname, $attribute, $op, $value);
                    $message = "Group check attribute added!";
                    $messageType = 'success';
                }
                break;
                
            case 'add_reply':
                $groupname = $_POST['groupname'] ?? '';
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? '=';
                $value = $_POST['value'] ?? '';
                
                if ($groupname && $attribute) {
                    $db->addGroupReplyAttribute($groupname, $attribute, $op, $value);
                    $message = "Group reply attribute added!";
                    $messageType = 'success';
                }
                break;
                
            case 'update_check':
                $id = $_POST['id'] ?? 0;
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? ':=';
                $value = $_POST['value'] ?? '';
                
                if ($id && $attribute) {
                    $db->updateGroupCheckAttribute($id, $attribute, $op, $value);
                    $message = "Group check attribute updated!";
                    $messageType = 'success';
                }
                break;
                
            case 'update_reply':
                $id = $_POST['id'] ?? 0;
                $attribute = $_POST['attribute'] ?? '';
                $op = $_POST['op'] ?? '=';
                $value = $_POST['value'] ?? '';
                
                if ($id && $attribute) {
                    $db->updateGroupReplyAttribute($id, $attribute, $op, $value);
                    $message = "Group reply attribute updated!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete_check':
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $db->deleteGroupCheckAttribute($id);
                    $message = "Group check attribute deleted!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete_reply':
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $db->deleteGroupReplyAttribute($id);
                    $message = "Group reply attribute deleted!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                $groupname = $_POST['groupname'] ?? '';
                if ($groupname) {
                    $db->deleteGroup($groupname);
                    $message = "Group '$groupname' deleted!";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all groups
$groups = $db->getAllGroups();
$selectedGroup = $_GET['group'] ?? '';
$groupCheckAttrs = [];
$groupReplyAttrs = [];
$groupMembers = [];

if ($selectedGroup) {
    $groupCheckAttrs = $db->getGroupCheckAttributes($selectedGroup);
    $groupReplyAttrs = $db->getGroupReplyAttributes($selectedGroup);
    $groupMembers = $db->getGroupMembers($selectedGroup);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Management - FreeRADIUS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">FreeRADIUS Manager</div>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="groups.php" class="active">Groups</a></li>
            <li><a href="nas.php">NAS Clients</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Group Management</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="page-grid">
            <!-- Group List -->
            <div class="card">
                <h2>Groups</h2>
                <form method="GET" class="search-form">
                    <input type="text" name="group" placeholder="Search group..." value="<?php echo htmlspecialchars($selectedGroup); ?>">
                    <button type="submit">Search</button>
                </form>
                
                <?php if ($selectedGroup): ?>
                    <div class="user-selected">
                        <p><strong>Selected:</strong> <?php echo htmlspecialchars($selectedGroup); ?></p>
                        <a href="groups.php" class="btn btn-secondary">Clear Selection</a>
                    </div>
                <?php endif; ?>
                
                <div class="user-list">
                    <?php foreach ($groups as $group): ?>
                        <a href="?group=<?php echo urlencode($group['groupname']); ?>" 
                           class="user-item <?php echo $selectedGroup === $group['groupname'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($group['groupname']); ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($groups)): ?>
                        <p class="empty-state">No groups found. Add a group below.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Details -->
            <div class="card">
                <?php if ($selectedGroup): ?>
                    <div class="user-header">
                        <h2><?php echo htmlspecialchars($selectedGroup); ?></h2>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this group?');" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="groupname" value="<?php echo htmlspecialchars($selectedGroup); ?>">
                            <button type="submit" class="btn btn-danger">Delete Group</button>
                        </form>
                    </div>
                    
                    <!-- Members -->
                    <div class="section">
                        <h3>Members</h3>
                        <?php if ($groupMembers): ?>
                            <ul class="group-list">
                                <?php foreach ($groupMembers as $member): ?>
                                    <li>
                                        <a href="users.php?user=<?php echo urlencode($member['username']); ?>">
                                            <?php echo htmlspecialchars($member['username']); ?>
                                        </a>
                                        <span class="priority">(Priority: <?php echo $member['priority']; ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-state">No users in this group.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Check Attributes -->
                    <div class="section">
                        <h3>Check Attributes (radgroupcheck)</h3>
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
                                <?php foreach ($groupCheckAttrs as $attr): ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_check">
                                            <input type="hidden" name="id" value="<?php echo $attr['id']; ?>">
                                            <td>
                                                <select name="attribute">
                                                    <option value="Auth-Type" <?php echo $attr['attribute'] === 'Auth-Type' ? 'selected' : ''; ?>>Auth-Type</option>
                                                    <option value="Access-Period" <?php echo $attr['attribute'] === 'Access-Period' ? 'selected' : ''; ?>>Access-Period</option>
                                                    <option value="Max-Daily-Session" <?php echo $attr['attribute'] === 'Max-Daily-Session' ? 'selected' : ''; ?>>Max-Daily-Session</option>
                                                    <option value="Max-Monthly-Session" <?php echo $attr['attribute'] === 'Max-Monthly-Session' ? 'selected' : ''; ?>>Max-Monthly-Session</option>
                                                    <option value="Simultaneous-Use" <?php echo $attr['attribute'] === 'Simultaneous-Use' ? 'selected' : ''; ?>>Simultaneous-Use</option>
                                                    <option value="Expiration" <?php echo $attr['attribute'] === 'Expiration' ? 'selected' : ''; ?>>Expiration</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="op">
                                                    <option value="=" <?php echo $attr['op'] === '=' ? 'selected' : ''; ?>>=</option>
                                                    <option value=":=" <?php echo $attr['op'] === ':=' ? 'selected' : ''; ?>>:=</option>
                                                    <option value="!=" <?php echo $attr['op'] === '!=' ? 'selected' : ''; ?>>!=</option>
                                                    <option value=">=" <?php echo $attr['op'] === '>=' ? 'selected' : ''; ?>>>=</option>
                                                    <option value="<=" <?php echo $attr['op'] === '<=' ? 'selected' : ''; ?>>⇐</option>
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
                            <input type="hidden" name="groupname" value="<?php echo htmlspecialchars($selectedGroup); ?>">
                            <h4>Add Check Attribute</h4>
                            <div class="form-row">
                                <select name="attribute" required>
                                    <option value="Auth-Type">Auth-Type</option>
                                    <option value="Access-Period">Access-Period</option>
                                    <option value="Max-Daily-Session">Max-Daily-Session</option>
                                    <option value="Max-Monthly-Session">Max-Monthly-Session</option>
                                    <option value="Simultaneous-Use">Simultaneous-Use</option>
                                    <option value="Expiration">Expiration</option>
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
                        <h3>Reply Attributes (radgroupreply)</h3>
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
                                <?php foreach ($groupReplyAttrs as $attr): ?>
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
                                                    <option value="Session-Timeout" <?php echo $attr['attribute'] === 'Session-Timeout' ? 'selected' : ''; ?>>Session-Timeout</option>
                                                    <option value="Idle-Timeout" <?php echo $attr['attribute'] === 'Idle-Timeout' ? 'selected' : ''; ?>>Idle-Timeout</option>
                                                    <option value="Bandwidth-Max-Up" <?php echo $attr['attribute'] === 'Bandwidth-Max-Up' ? 'selected' : ''; ?>>Bandwidth-Max-Up</option>
                                                    <option value="Bandwidth-Max-Down" <?php echo $attr['attribute'] === 'Bandwidth-Max-Down' ? 'selected' : ''; ?>>Bandwidth-Max-Down</option>
                                                    <option value="Acct-Interim-Interval" <?php echo $attr['attribute'] === 'Acct-Interim-Interval' ? 'selected' : ''; ?>>Acct-Interim-Interval</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="op">
                                                    <option value="=" <?php echo $attr['op'] === '=' ? 'selected' : ''; ?>>=</option>
                                                    <option value=":=" <?php echo $attr['op'] === ':=' ? 'selected' : ''; ?>>:=</option>
                                                    <option value="+=" <?php echo $attr['op'] === '+=' ? 'selected' : ''; ?>>+=</option>
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
                            <input type="hidden" name="groupname" value="<?php echo htmlspecialchars($selectedGroup); ?>">
                            <h4>Add Reply Attribute</h4>
                            <div class="form-row">
                                <select name="attribute" required>
                                    <option value="Framed-Protocol">Framed-Protocol</option>
                                    <option value="Framed-IP-Address">Framed-IP-Address</option>
                                    <option value="Framed-IP-Netmask">Framed-IP-Netmask</option>
                                    <option value="Framed-Route">Framed-Route</option>
                                    <option value="Session-Timeout">Session-Timeout</option>
                                    <option value="Idle-Timeout">Idle-Timeout</option>
                                    <option value="Bandwidth-Max-Up">Bandwidth-Max-Up</option>
                                    <option value="Bandwidth-Max-Down">Bandwidth-Max-Down</option>
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
                        <h2>Add New Group</h2>
                        <form method="POST" class="add-form">
                            <input type="hidden" name="action" value="add">
                            <div class="form-row">
                                <input type="text" name="groupname" placeholder="Group Name" required>
                                <button type="submit" class="btn btn-primary">Create Group</button>
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

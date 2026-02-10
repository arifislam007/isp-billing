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
                $nasname = $_POST['nasname'] ?? '';
                $shortname = $_POST['shortname'] ?? '';
                $type = $_POST['type'] ?? 'other';
                $secret = $_POST['secret'] ?? '';
                $description = $_POST['description'] ?? '';
                
                if ($nasname && $shortname && $secret) {
                    if ($db->addNasClient($nasname, $shortname, $type, $secret, $description)) {
                        $message = "NAS client '$shortname' added successfully!";
                        $messageType = 'success';
                    }
                } else {
                    $message = "NAS IP, shortname, and secret are required!";
                    $messageType = 'error';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $nasname = $_POST['nasname'] ?? '';
                $shortname = $_POST['shortname'] ?? '';
                $type = $_POST['type'] ?? 'other';
                $secret = $_POST['secret'] ?? '';
                $description = $_POST['description'] ?? '';
                
                if ($id && $nasname && $shortname && $secret) {
                    if ($db->updateNasClient($id, $nasname, $shortname, $type, $secret, $description)) {
                        $message = "NAS client updated successfully!";
                        $messageType = 'success';
                    }
                } else {
                    $message = "All fields are required!";
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $db->deleteNasClient($id);
                    $message = "NAS client deleted!";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all NAS clients
$nasClients = $db->getAllNasClients();
$editingClient = null;

if (isset($_GET['edit'])) {
    $editingClient = $db->getNasClient((int)$_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAS Client Management - FreeRADIUS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">FreeRADIUS Manager</div>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="groups.php">Groups</a></li>
            <li><a href="nas.php" class="active">NAS Clients</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>NAS Client Management</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="page-grid">
            <!-- NAS List -->
            <div class="card">
                <h2>NAS Clients</h2>
                <div class="nas-list">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Shortname</th>
                                <th>NAS IP</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nasClients as $nas): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nas['shortname']); ?></td>
                                    <td><?php echo htmlspecialchars($nas['nasname']); ?></td>
                                    <td><?php echo htmlspecialchars($nas['type']); ?></td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $nas['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Delete this NAS client?');" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $nas['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($nasClients)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">No NAS clients configured.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <div class="card">
                <h2><?php echo $editingClient ? 'Edit NAS Client' : 'Add New NAS Client'; ?></h2>
                
                <form method="POST" class="nas-form">
                    <input type="hidden" name="action" value="<?php echo $editingClient ? 'edit' : 'add'; ?>">
                    <?php if ($editingClient): ?>
                        <input type="hidden" name="id" value="<?php echo $editingClient['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nasname">NAS IP Address *</label>
                        <input type="text" id="nasname" name="nasname" 
                               value="<?php echo htmlspecialchars($editingClient['nasname'] ?? ''); ?>"
                               placeholder="192.168.1.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortname">Shortname *</label>
                        <input type="text" id="shortname" name="shortname" 
                               value="<?php echo htmlspecialchars($editingClient['shortname'] ?? ''); ?>"
                               placeholder="router1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="other" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            <option value="wireless" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'wireless') ? 'selected' : ''; ?>>Wireless Access Point</option>
                            <option value="mikrotik" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'mikrotik') ? 'selected' : ''; ?>>Mikrotik Router</option>
                            <option value="ubiquiti" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'ubiquiti') ? 'selected' : ''; ?>>Ubiquiti UniFi</option>
                            <option value="cisco" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'cisco') ? 'selected' : ''; ?>>Cisco WLC</option>
                            <option value="aruba" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'aruba') ? 'selected' : ''; ?>>Aruba Controller</option>
                            <option value="meraki" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'meraki') ? 'selected' : ''; ?>>Meraki AP</option>
                            <option value="tp-link" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'tp-link') ? 'selected' : ''; ?>>TP-Link</option>
                            <option value="openwrt" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'openwrt') ? 'selected' : ''; ?>>OpenWRT</option>
                            <option value="dd-wrt" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'dd-wrt') ? 'selected' : ''; ?>>DD-WRT</option>
                            <option value="huawei" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'huawei') ? 'selected' : ''; ?>>Huawei</option>
                            <option value="juniper" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'juniper') ? 'selected' : ''; ?>>Juniper</option>
                            <option value="dlink" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'dlink') ? 'selected' : ''; ?>>D-Link</option>
                            <option value="zyxel" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'zyxel') ? 'selected' : ''; ?>>ZyXEL</option>
                            <option value="router" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'router') ? 'selected' : ''; ?>>Router</option>
                            <option value="switch" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'switch') ? 'selected' : ''; ?>>Switch</option>
                            <option value="firewall" <?php echo (isset($editingClient['type']) && $editingClient['type'] === 'firewall') ? 'selected' : ''; ?>>Firewall</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="secret">RADIUS Secret *</label>
                        <input type="text" id="secret" name="secret" 
                               value="<?php echo htmlspecialchars($editingClient['secret'] ?? ''); ?>"
                               placeholder="shared_secret" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Optional description"><?php echo htmlspecialchars($editingClient['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editingClient ? 'Update NAS Client' : 'Add NAS Client'; ?>
                        </button>
                        <?php if ($editingClient): ?>
                            <a href="nas.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

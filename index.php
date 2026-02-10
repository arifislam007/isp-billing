<?php
require_once 'db.php';

$db = new RadDB();
$stats = $db->getStats();
$wifiStats = $db->getWifiStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeRADIUS Management Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">FreeRADIUS Manager</div>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="groups.php">Groups</a></li>
            <li><a href="nas.php">NAS Clients</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $stats['users']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3>Total Groups</h3>
                    <p class="stat-number"><?php echo $stats['groups']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🌐</div>
                <div class="stat-info">
                    <h3>NAS Clients</h3>
                    <p class="stat-number"><?php echo $stats['nas_clients']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📡</div>
                <div class="stat-info">
                    <h3>Active Sessions</h3>
                    <p class="stat-number"><?php echo $stats['active_sessions']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3>Today's Sessions</h3>
                    <p class="stat-number"><?php echo $wifiStats['today_sessions']; ?></p>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="users.php?action=add" class="action-card">
                    <span class="action-icon">➕</span>
                    <span>Add New User</span>
                </a>
                <a href="groups.php?action=add" class="action-card">
                    <span class="action-icon">➕</span>
                    <span>Add New Group</span>
                </a>
                <a href="nas.php?action=add" class="action-card">
                    <span class="action-icon">➕</span>
                    <span>Add NAS Client</span>
                </a>
                <a href="users.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <span>Manage Users</span>
                </a>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

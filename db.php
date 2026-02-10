<?php
require_once 'config.php';

/**
 * FreeRADIUS Database Helper Class
 * Handles all CRUD operations for FreeRADIUS tables
 */
class RadDB {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    // ==================== USER MANAGEMENT ====================
    
    /**
     * Get all users from radcheck
     */
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT DISTINCT username FROM radcheck ORDER BY username");
        return $stmt->fetchAll();
    }
    
    /**
     * Get user check attributes
     */
    public function getUserCheckAttributes($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM radcheck WHERE username = ? ORDER BY id");
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user reply attributes
     */
    public function getUserReplyAttributes($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM radreply WHERE username = ? ORDER BY id");
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add new user (creates username entry in radcheck)
     */
    public function addUser($username, $attribute = 'Cleartext-Password', $op = ':=', $value = '') {
        $stmt = $this->pdo->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $attribute, $op, $value]);
    }
    
    /**
     * Add user check attribute
     */
    public function addUserCheckAttribute($username, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $attribute, $op, $value]);
    }
    
    /**
     * Add user reply attribute
     */
    public function addUserReplyAttribute($username, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $attribute, $op, $value]);
    }
    
    /**
     * Update check attribute
     */
    public function updateCheckAttribute($id, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("UPDATE radcheck SET attribute = ?, op = ?, value = ? WHERE id = ?");
        return $stmt->execute([$attribute, $op, $value, $id]);
    }
    
    /**
     * Update reply attribute
     */
    public function updateReplyAttribute($id, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("UPDATE radreply SET attribute = ?, op = ?, value = ? WHERE id = ?");
        return $stmt->execute([$attribute, $op, $value, $id]);
    }
    
    /**
     * Delete check attribute
     */
    public function deleteCheckAttribute($id) {
        $stmt = $this->pdo->prepare("DELETE FROM radcheck WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete reply attribute
     */
    public function deleteReplyAttribute($id) {
        $stmt = $this->pdo->prepare("DELETE FROM radreply WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete user (all entries)
     */
    public function deleteUser($username) {
        $this->pdo->prepare("DELETE FROM radcheck WHERE username = ?")->execute([$username]);
        $this->pdo->prepare("DELETE FROM radreply WHERE username = ?")->execute([$username]);
        $this->pdo->prepare("DELETE FROM radusergroup WHERE username = ?")->execute([$username]);
        return true;
    }
    
    // ==================== GROUP MANAGEMENT ====================
    
    /**
     * Get all groups
     */
    public function getAllGroups() {
        $stmt = $this->pdo->query("SELECT DISTINCT groupname FROM radgroupcheck ORDER BY groupname");
        return $stmt->fetchAll();
    }
    
    /**
     * Get group check attributes
     */
    public function getGroupCheckAttributes($groupname) {
        $stmt = $this->pdo->prepare("SELECT * FROM radgroupcheck WHERE groupname = ? ORDER BY id");
        $stmt->execute([$groupname]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get group reply attributes
     */
    public function getGroupReplyAttributes($groupname) {
        $stmt = $this->pdo->prepare("SELECT * FROM radgroupreply WHERE groupname = ? ORDER BY id");
        $stmt->execute([$groupname]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get users in a group
     */
    public function getGroupMembers($groupname) {
        $stmt = $this->pdo->prepare("SELECT username, priority FROM radusergroup WHERE groupname = ? ORDER BY username");
        $stmt->execute([$groupname]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add group
     */
    public function addGroup($groupname) {
        // Add a default attribute to make the group exist
        $stmt = $this->pdo->prepare("INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES (?, 'Auth-Type', ':=', 'Accept')");
        return $stmt->execute([$groupname]);
    }
    
    /**
     * Add group check attribute
     */
    public function addGroupCheckAttribute($groupname, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$groupname, $attribute, $op, $value]);
    }
    
    /**
     * Add group reply attribute
     */
    public function addGroupReplyAttribute($groupname, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$groupname, $attribute, $op, $value]);
    }
    
    /**
     * Add user to group
     */
    public function addUserToGroup($username, $groupname, $priority = 1) {
        $stmt = $this->pdo->prepare("INSERT INTO radusergroup (username, groupname, priority) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $groupname, $priority]);
    }
    
    /**
     * Remove user from group
     */
    public function removeUserFromGroup($username, $groupname) {
        $stmt = $this->pdo->prepare("DELETE FROM radusergroup WHERE username = ? AND groupname = ?");
        return $stmt->execute([$username, $groupname]);
    }
    
    /**
     * Update group check attribute
     */
    public function updateGroupCheckAttribute($id, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("UPDATE radgroupcheck SET attribute = ?, op = ?, value = ? WHERE id = ?");
        return $stmt->execute([$attribute, $op, $value, $id]);
    }
    
    /**
     * Update group reply attribute
     */
    public function updateGroupReplyAttribute($id, $attribute, $op, $value) {
        $stmt = $this->pdo->prepare("UPDATE radgroupreply SET attribute = ?, op = ?, value = ? WHERE id = ?");
        return $stmt->execute([$attribute, $op, $value, $id]);
    }
    
    /**
     * Delete group check attribute
     */
    public function deleteGroupCheckAttribute($id) {
        $stmt = $this->pdo->prepare("DELETE FROM radgroupcheck WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete group reply attribute
     */
    public function deleteGroupReplyAttribute($id) {
        $stmt = $this->pdo->prepare("DELETE FROM radgroupreply WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete group
     */
    public function deleteGroup($groupname) {
        $this->pdo->prepare("DELETE FROM radgroupcheck WHERE groupname = ?")->execute([$groupname]);
        $this->pdo->prepare("DELETE FROM radgroupreply WHERE groupname = ?")->execute([$groupname]);
        $this->pdo->prepare("DELETE FROM radusergroup WHERE groupname = ?")->execute([$groupname]);
        return true;
    }
    
    // ==================== NAS CLIENT MANAGEMENT ====================
    
    /**
     * Get all NAS clients
     */
    public function getAllNasClients() {
        $stmt = $this->pdo->query("SELECT * FROM nas ORDER BY nasname");
        return $stmt->fetchAll();
    }
    
    /**
     * Get NAS client by ID
     */
    public function getNasClient($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM nas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Add NAS client
     */
    public function addNasClient($nasname, $shortname, $type, $secret, $description = '') {
        $stmt = $this->pdo->prepare("INSERT INTO nas (nasname, shortname, type, secret, description) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$nasname, $shortname, $type, $secret, $description]);
    }
    
    /**
     * Update NAS client
     */
    public function updateNasClient($id, $nasname, $shortname, $type, $secret, $description) {
        $stmt = $this->pdo->prepare("UPDATE nas SET nasname = ?, shortname = ?, type = ?, secret = ?, description = ? WHERE id = ?");
        return $stmt->execute([$nasname, $shortname, $type, $secret, $description, $id]);
    }
    
    /**
     * Delete NAS client
     */
    public function deleteNasClient($id) {
        $stmt = $this->pdo->prepare("DELETE FROM nas WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // ==================== STATISTICS ====================
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        $stats = [];
        
        $stats['users'] = $this->pdo->query("SELECT COUNT(DISTINCT username) as count FROM radcheck")->fetch()['count'];
        $stats['groups'] = $this->pdo->query("SELECT COUNT(DISTINCT groupname) as count FROM radgroupcheck")->fetch()['count'];
        $stats['nas_clients'] = $this->pdo->query("SELECT COUNT(*) as count FROM nas")->fetch()['count'];
        $stats['active_sessions'] = $this->pdo->query("SELECT COUNT(*) as count FROM radacct WHERE acctstoptime IS NULL")->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Get all groups for dropdown
     */
    public function getGroupsForSelect() {
        $stmt = $this->pdo->query("SELECT DISTINCT groupname FROM radgroupcheck ORDER BY groupname");
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's groups
     */
    public function getUserGroups($username) {
        $stmt = $this->pdo->prepare("SELECT groupname, priority FROM radusergroup WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }
    
    // ==================== WIFI-SPECIFIC ATTRIBUTES ====================
    
    /**
     * Get WiFi-specific check attributes
     */
    public function getWifiCheckAttributes() {
        return [
            'Cleartext-Password' => 'Cleartext-Password',
            'NT-Password' => 'NT-Password',
            'MD5-Password' => 'MD5-Password',
            'Expiration' => 'Expiration',
            'Max-Daily-Session' => 'Max-Daily-Session',
            'Max-Monthly-Session' => 'Max-Monthly-Session',
            'Max-Session-Timeout' => 'Max-Session-Timeout',
            'Simultaneous-Use' => 'Simultaneous-Use',
            'Session-Timeout' => 'Session-Timeout',
            'Idle-Timeout' => 'Idle-Timeout',
            'WISPr-Bandwidth-Max-Up' => 'WISPr-Bandwidth-Max-Up',
            'WISPr-Bandwidth-Max-Down' => 'WISPr-Bandwidth-Max-Down',
            'WISPr-Session-Terminate-Time' => 'WISPr-Session-Terminate-Time',
            'ChilliSpot-Max-Total-Gigawords' => 'ChilliSpot-Max-Total-Gigawords',
            'ChilliSpot-Max-Input-Gigawords' => 'ChilliSpot-Max-Input-Gigawords',
            'ChilliSpot-Max-Output-Gigawords' => 'ChilliSpot-Max-Output-Gigawords',
        ];
    }
    
    /**
     * Get WiFi-specific reply attributes
     */
    public function getWifiReplyAttributes() {
        return [
            'Framed-Protocol' => 'Framed-Protocol',
            'Service-Type' => 'Service-Type',
            'Framed-IP-Address' => 'Framed-IP-Address',
            'Framed-IP-Netmask' => 'Framed-IP-Netmask',
            'Framed-Route' => 'Framed-Route',
            'Framed-MTU' => 'Framed-MTU',
            'Session-Timeout' => 'Session-Timeout',
            'Idle-Timeout' => 'Idle-Timeout',
            'Termination-Action' => 'Termination-Action',
            'Acct-Interim-Interval' => 'Acct-Interim-Interval',
            'WISPr-Bandwidth-Max-Up' => 'WISPr-Bandwidth-Max-Up',
            'WISPr-Bandwidth-Max-Down' => 'WISPr-Bandwidth-Max-Down',
            'WISPr-Redirection' => 'WISPr-Redirection',
            'WISPr-Session-Terminate-Time' => 'WISPr-Session-Terminate-Time',
            'WISPr-Billing-Class' => 'WISPr-Billing-Class',
            'ChilliSpot-Location' => 'ChilliSpot-Location',
            'ChilliSpot-Version' => 'ChilliSpot-Version',
            'Mikrotik-Rate-Limit' => 'Mikrotik-Rate-Limit',
            'Ascend-Data-Rate' => 'Ascend-Data-Rate',
            'Ascend-Xmit-Rate' => 'Ascend-Xmit-Rate',
        ];
    }
    
    /**
     * Get WiFi NAS types
     */
    public function getWifiNasTypes() {
        return [
            'wireless' => 'Wireless Access Point',
            'mikrotik' => 'Mikrotik Router',
            'ubiquiti' => 'Ubiquiti UniFi',
            'cisco' => 'Cisco WLC',
            ' Aruba' => 'Aruba Controller',
            'meraki' => 'Meraki AP',
            'tp-link' => 'TP-Link',
            'openwrt' => 'OpenWRT',
            'dd-wrt' => 'DD-WRT',
            'wireless' => 'Generic Wireless',
        ];
    }
    
    /**
     * Create WiFi user with common settings
     */
    public function createWifiUser($username, $password, $sessionTimeout = '3600', $idleTimeout = '600') {
        try {
            $this->pdo->beginTransaction();
            
            // Add password check attribute
            $stmt = $this->pdo->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)");
            $stmt->execute([$username, $password]);
            
            // Add service type reply
            $stmt = $this->pdo->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Service-Type', '=', 'Framed-User')");
            $stmt->execute([$username]);
            
            // Add framed protocol
            $stmt = $this->pdo->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Framed-Protocol', '=', 'PPP')");
            $stmt->execute([$username]);
            
            // Add session timeout
            if ($sessionTimeout) {
                $stmt = $this->pdo->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Session-Timeout', '=', ?)");
                $stmt->execute([$username, $sessionTimeout]);
            }
            
            // Add idle timeout
            if ($idleTimeout) {
                $stmt = $this->pdo->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Idle-Timeout', '=', ?)");
                $stmt->execute([$username, $idleTimeout]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Create WiFi hotspot group with bandwidth limits
     */
    public function createWifiHotspotGroup($groupname, $uploadSpeed = '512000', $downloadSpeed = '1024000') {
        try {
            $this->pdo->beginTransaction();
            
            // Add group with auth type
            $stmt = $this->pdo->prepare("INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES (?, 'Auth-Type', ':=', 'Accept')");
            $stmt->execute([$groupname]);
            
            // Add service type reply
            $stmt = $this->pdo->prepare("INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, 'Service-Type', '=', 'Framed-User')");
            $stmt->execute([$groupname]);
            
            // Add framed protocol
            $stmt = $this->pdo->prepare("INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, 'Framed-Protocol', '=', 'PPP')");
            $stmt->execute([$groupname]);
            
            // Add bandwidth limits (Mikrotik style)
            if ($uploadSpeed || $downloadSpeed) {
                $rateLimit = "{$downloadSpeed}k/{$uploadSpeed}k";
                $stmt = $this->pdo->prepare("INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, 'Mikrotik-Rate-Limit', '=', ?)");
                $stmt->execute([$groupname, $rateLimit]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get WiFi-specific statistics
     */
    public function getWifiStats() {
        $stats = [];
        
        // Today's active sessions
        $stats['today_sessions'] = $this->pdo->query("SELECT COUNT(*) FROM radacct WHERE DATE(acctstarttime) = CURDATE()")->fetch()['count'];
        
        // Total data usage today (in bytes)
        $stats['today_traffic'] = $this->pdo->query("SELECT COALESCE(SUM(acctinputoctets) + SUM(acctoutputoctets), 0) FROM radacct WHERE DATE(acctstarttime) = CURDATE()")->fetch()['count'];
        
        // Average session duration
        $stats['avg_session'] = $this->pdo->query("SELECT COALESCE(AVG(TIMESTAMPDIFF(SECOND, acctstarttime, COALESCE(acctstoptime, NOW()))), 0) FROM radacct WHERE acctstoptime IS NOT NULL")->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Add WiFi NAS client (Access Point)
     */
    public function addWifiNasClient($nasname, $shortname, $type, $secret, $description = '') {
        return $this->addNasClient($nasname, $shortname, $type, $secret, $description);
    }
    
    /**
     * Get active WiFi sessions
     */
    public function getActiveWifiSessions() {
        $stmt = $this->pdo->query("
            SELECT r.*, u.groupname 
            FROM radacct r 
            LEFT JOIN radusergroup u ON r.username = u.username 
            WHERE r.acctstoptime IS NULL 
            ORDER BY r.acctstarttime DESC
        ");
        return $stmt->fetchAll();
    }
}

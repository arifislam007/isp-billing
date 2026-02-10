# FreeRADIUS Management System

A PHP-based web application for managing FreeRADIUS server with MariaDB backend. Designed for ISP billing and WiFi hotspot management.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Configuration](#database-configuration)
- [User Management](#user-management)
  - [Create User](#create-user)
  - [Manage User Attributes](#manage-user-attributes)
- [Group Management](#group-management)
  - [Create Group](#create-group)
  - [Add Group Attributes](#add-group-attributes)
- [NAS Client Management](#nas-client-management)
  - [Add NAS Client](#add-nas-client)
- [User-Group Assignment](#user-group-assignment)
- [WiFi Authentication Setup](#wifi-authentication-setup)
  - [Configure WiFi Access Point](#configure-wifi-access-point)
  - [Create WiFi Hotspot Group](#create-wifi-hotspot-group)
  - [Bandwidth Management](#bandwidth-management)

## Features

- **Dashboard**: Overview of users, groups, NAS clients, and active sessions
- **User Management**: Create/manage users with RADIUS check/reply attributes
- **Group Management**: Create groups with shared policies and bandwidth limits
- **NAS Client Management**: Add/configure WiFi access points and routers
- **WiFi Authentication**: Full support for WiFi hotspot authentication
- **Bandwidth Control**: WISPr and Mikrotik rate limiting support

## Requirements

- PHP 7.4+
- MariaDB/MySQL with FreeRADIUS schema
- Web server (Apache/Nginx)
- FreeRADIUS 3.x

## Installation

1. **Clone the repository**:
   ```bash
   cd /var/www/html
   git clone https://github.com/arifislam007/isp-billing.git
   cd isp-billing
   git checkout v4.0
   ```

2. **Configure database connection**:
   Edit `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'radius');
   define('DB_USER', 'billing');
   define('DB_PASS', 'Billing123');
   ```

3. **Access the application**:
   Open browser: `http://your-server/isp-billing/`

## Database Configuration

Ensure your FreeRADIUS database schema is installed:
```bash
# For Debian/Ubuntu
sudo apt-get install freeradius-mysql freeradius-utils

# Import schema
sudo mysql radius < /etc/freeradius/3.0/mods-config/sql/main/mysql/schema.sql
```

Grant permissions:
```sql
GRANT ALL ON radius.* TO 'billing'@'localhost' IDENTIFIED BY 'Billing123';
FLUSH PRIVILEGES;
```

## User Management

### Create User

1. Navigate to **Users** in the navigation menu
2. Scroll to "Add New User" section
3. Enter username and password
4. Click **Create User**

```
Username: wifiuser1
Password: securepassword123
```

### Manage User Attributes

After creating a user, click on their name to manage:

#### Check Attributes (radcheck)
Used for authentication conditions:
- **Cleartext-Password** - User's password
- **Expiration** - Account expiry date (YYYY-MM-DD)
- **Max-Daily-Session** - Max seconds per day
- **Max-Monthly-Session** - Max seconds per month
- **Simultaneous-Use** - Number of concurrent sessions
- **Session-Timeout** - Maximum session duration
- **Idle-Timeout** - Time before disconnect on inactivity

#### Reply Attributes (radreply)
Returned to NAS after authentication:
- **Framed-Protocol** = PPP
- **Service-Type** = Framed-User
- **Framed-IP-Address** = Assign static IP
- **Session-Timeout** = Session length
- **WISPr-Bandwidth-Max-Up/Down** = Bandwidth limits

**Example - WiFi User with 1GB daily limit**:
```
Check Attribute: Max-Daily-Session := 3600
Reply Attribute: WISPr-Bandwidth-Max-Down := 1024000
Reply Attribute: WISPr-Bandwidth-Max-Up := 512000
```

## Group Management

### Create Group

1. Navigate to **Groups** in the navigation menu
2. Enter group name
3. Click **Create Group**

```
Group Name: premium_users
```

### Add Group Attributes

Groups apply settings to all members:

**Example - Premium Hotspot Package**:
```
Group: premium_users

Check Attributes:
- Auth-Type := Accept

Reply Attributes:
- Service-Type := Framed-User
- Framed-Protocol := PPP
- Session-Timeout := 86400
- WISPr-Bandwidth-Max-Down := 2048000
- WISPr-Bandwidth-Max-Up := 1024000
- Mikrotik-Rate-Limit := 2048k/1024k
```

### Bandwidth Limit Formats

| Format | Example | Description |
|--------|---------|-------------|
| Mikrotik | `2048k/1024k` | 2Mbps down / 1Mbps up |
| WISPr (bytes/sec) | `256000` | 256KB/sec |
| WISPr (bits/sec) | `2048000` | 2Mbps |

## NAS Client Management

### Add NAS Client

1. Navigate to **NAS Clients** in the navigation menu
2. Fill in the form:

```
NAS IP Address: 192.168.1.1
Shortname: office_ap1
Type: Wireless Access Point
RADIUS Secret: testing123
Description: Office WiFi AP 1
```

### Supported NAS Types

- **Wireless Access Point** - Generic WiFi AP
- **Mikrotik Router** - RouterOS devices
- **Ubiquiti UniFi** - Ubiquiti access points
- **Cisco WLC** - Cisco Wireless LAN Controller
- **Aruba Controller** - Aruba access points
- **Meraki AP** - Cisco Meraki devices
- **TP-Link** - TP-Link WiFi devices
- **OpenWRT** - OpenWRT-based routers
- **DD-WRT** - DD-WRT routers

## User-Group Assignment

### Assign User to Group

1. Go to **Users** and select a user
2. In "Groups" section, select group from dropdown
3. Set priority (higher = applied first)
4. Click **Add to Group**

**Example**:
```
User: wifiuser1
Add to Group: premium_users
Priority: 1
```

### Group Priority

- Users can belong to multiple groups
- Attributes applied in priority order (highest first)
- Lower numbers = lower priority

## WiFi Authentication Setup

### Configure WiFi Access Point

1. **Access your WiFi AP configuration**
2. **Enable RADIUS/802.1X authentication**
3. **Configure RADIUS settings**:

```
RADIUS Server: your-server-ip
Authentication Port: 1812
Accounting Port: 1813
Shared Secret: (from NAS client setup)
```

### Create WiFi Hotspot Group

1. Go to **Groups** → Add New Group
2. Name: `hotspot_premium`
3. Add attributes:

**Check Attributes**:
```
Auth-Type := Accept
```

**Reply Attributes**:
```
Service-Type := Framed-User
Framed-Protocol := PPP
Session-Timeout := 3600
Idle-Timeout := 600
WISPr-Bandwidth-Max-Down := 1024000
WISPr-Bandwidth-Max-Up := 512000
Mikrotik-Rate-Limit := 1024k/512k
```

### Bandwidth Management

#### Mikrotik Router Format
```
Mikrotik-Rate-Limit := "2048k/1024k"
```

Format: `rx-rate/tx-rate` (bits/sec with k/m suffix)

#### WISPr Format (bytes/sec)
```
WISPr-Bandwidth-Max-Down := 256000
WISPr-Bandwidth-Max-Up := 128000
```

Values are in **bytes per second**:
- 256000 bytes/sec = ~2 Mbps
- 1024000 bytes/sec = ~8 Mbps

#### Common Bandwidth Settings

| Speed | WISPr (bytes/sec) | Mikrotik |
|-------|-------------------|----------|
| 1 Mbps | 128000 | 128k/64k |
| 2 Mbps | 256000 | 256k/128k |
| 5 Mbps | 640000 | 640k/320k |
| 10 Mbps | 1280000 | 1M/512k |
| 20 Mbps | 2560000 | 2M/1M |

## Testing

### Test with radtest

```bash
radtest wifiuser1 password123 localhost 0 testing123
```

### Check Active Sessions

```sql
SELECT username, nasipaddress, acctstarttime, acctstoptime 
FROM radacct 
WHERE acctstoptime IS NULL;
```

## Troubleshooting

### Cannot connect?

1. Check FreeRADIUS is running:
   ```bash
   sudo systemctl status freeradius
   sudo freeradius -X
   ```

2. Verify database connection in `config.php`

3. Check RADIUS packets aren't blocked by firewall:
   ```bash
   sudo ufw allow 1812/udp
   sudo ufw allow 1813/udp
   ```

### Users not authenticating?

1. Verify NAS client is added with correct IP and secret
2. Check attribute syntax (especially quotes for Mikrotik)
3. Test with `radtest` command first

### Bandwidth limits not working?

1. Ensure NAS supports the attribute type
2. Mikrotik requires `Mikrotik-Rate-Limit`
3. Standard APs use WISPr attributes
4. Some APs require `retain-location` policy

## Support

For issues or questions:
- Check FreeRADIUS logs: `/var/log/freeradius/radius.log`
- Enable debug mode: `sudo freeradius -X`
- Review SQL queries for errors

---

**Repository**: https://github.com/arifislam007/isp-billing  
**Branch**: v4.0

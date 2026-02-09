# ISP Billing System

A comprehensive ISP billing and management system built with PHP, FreeRADIUS, and MariaDB.

## Features

- **User Management**: Add, edit, and manage customer accounts
- **Package Management**: Create and manage internet packages with speed limits
- **Customer Portal**: Assign packages to customers with automatic RADIUS integration
- **Invoice Generation**: Create and manage invoices with tax support
- **Payment Management**: Record and track payments with multiple payment methods
- **Reports**: Revenue reports, customer reports, and analytics
- **NAS Management**: Manage Network Access Servers for RADIUS authentication
- **Admin Users**: Role-based access control (Admin, Manager, Support)

## Requirements

- PHP 7.4 or higher
- MariaDB 10.x or higher
- FreeRADIUS 3.x (pre-installed)
- Apache or Nginx web server
- Bootstrap 5.x (included via CDN)
- Font Awesome (included via CDN)

## Installation

### 1. Database Setup

First, ensure you have created the databases in MariaDB:

```sql
CREATE DATABASE billing;
CREATE DATABASE radius;
```

Then run the schema setup by accessing the schema file through your browser or command line:

```bash
php schema.php
```

Or import the tables manually via phpMyAdmin or command line:

```bash
mysql -u billing -p billing < schema.php
```

### 2. Configure Database Connection

Edit `config.php` and update the database credentials:

```php
// Billing Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'billing');
define('DB_USER', 'billing');
define('DB_PASS', 'Billing123');

// FreeRADIUS Database
define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'billing');
define('RADIUS_DB_PASS', 'Billing123');
```

### 3. Set Permissions

Ensure proper permissions for file uploads and sessions:

```bash
chmod 755 .
chmod 644 *.php
```

### 4. Access the Application

Open your browser and navigate to:
`http://localhost/isp-billing/`

## Default Login

- **Username**: admin
- **Password**: admin

## File Structure

```
isp-billing/
├── config.php           # Configuration file
├── database.php          # Database connection class
├── schema.php            # Database schema setup
├── auth.php              # Authentication & session management
├── functions.php         # Helper functions
├── ajax.php              # AJAX handler
├── login.php             # Login page
├── logout.php            # Logout handler
├── dashboard.php         # Main dashboard
├── header.php           # Common header
├── footer.php           # Common footer
├── css/
│   └── style.css        # Custom styles
├── js/
│   └── app.js           # JavaScript utilities
├── customers.php         # Customer management
├── add-customer.php     # Add new customer
├── edit-customer.php    # Edit customer
├── customer-view.php    # Customer details view
├── assign-package.php   # Assign package to customer
├── packages.php         # Package management
├── add-package.php      # Add new package
├── edit-package.php     # Edit package
├── invoices.php         # Invoice management
├── add-invoice.php      # Create invoice
├── invoice-view.php     # Invoice details
├── invoice-print.php    # Print-friendly invoice
├── payments.php         # Payment management
├── add-payment.php      # Record payment
├── reports.php          # Reports dashboard
├── revenue-report.php   # Revenue report
├── customer-report.php  # Customer report
├── nas.php              # NAS management
├── add-nas.php          # Add new NAS
├── edit-nas.php         # Edit NAS
├── admin-users.php      # Admin user management
├── add-admin.php        # Add new admin
├── edit-admin.php       # Edit admin
├── profile.php          # User profile
└── change-password.php  # Change password
```

## FreeRADIUS Integration

The system automatically integrates with FreeRADIUS for user authentication:

1. **Customer Creation**: When a new customer is added, their credentials are automatically added to the `radcheck` table in the RADIUS database.

2. **Package Assignment**: When a package is assigned, speed limits are automatically configured via `radreply` table:
   - `WISPr-Bandwidth-Max-Down`: Download speed limit
   - `WISPr-Bandwidth-Max-Up`: Upload speed limit

3. **NAS Management**: NAS devices are synchronized between the billing and RADIUS databases.

## Payment Methods Supported

- Cash
- Bank Transfer
- Bkash
- Nagad
- Card
- Other

## Billing Cycles

- Daily
- Weekly
- Monthly (default)
- Quarterly
- Yearly

## Security Features

- Password hashing with PHP's `password_hash()`
- CSRF protection on all forms
- Session management with security flags
- Input sanitization
- Role-based access control

## Customization

### Adding Custom Payment Methods

Edit `add-payment.php` and `payments.php` to add new payment methods.

### Modifying Invoice Template

Edit `invoice-print.php` to customize the invoice layout.

### Adding Custom Reports

Create a new report file and add it to the reports dropdown in `header.php`.

## License

This project is open source and available for free use.

## Support

For issues and feature requests, please create an issue in the project repository.

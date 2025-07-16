# 🚨 HTTP 500 Error - Quick Fix Guide

## Step 1: Basic Testing
Go to your domain and try these URLs in order:

1. **yoursite.com/test.php** - Basic PHP test
2. **yoursite.com/setup.php** - System setup and diagnosis
3. **yoursite.com/debug.php** - Detailed error information

## Step 2: Check the Results

### If test.php works:
- PHP is working correctly
- Move to setup.php

### If test.php shows errors:
- Check PHP version (needs 7.4+)
- Install missing extensions
- Check file permissions

### If setup.php shows database errors:
1. Create database: `CREATE DATABASE hotel_channel_manager;`
2. Import schema: `mysql -u root -p hotel_channel_manager < config/database.sql`
3. Update credentials in `simple_config.php`

## Step 3: Try Simple Login
Once setup.php shows all green checkmarks:
- Go to **yoursite.com/admin/simple_login.php**
- Login with: admin / admin123

## Step 4: Full System
After simple login works:
- Try **yoursite.com/admin/login.php**
- Same credentials: admin / admin123

## Common Issues & Solutions

### Database Connection Failed
```php
// Edit simple_config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_channel_manager');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### Missing Directories
```bash
mkdir logs uploads
chmod 755 logs uploads
```

### PHP Extensions Missing
Install required extensions:
- php-curl
- php-pdo-mysql
- php-json
- php-mbstring

### Still Getting 500 Error?
1. Check server error logs
2. Enable PHP error display
3. Check file permissions (755 for directories, 644 for files)
4. Verify all files uploaded correctly

## Quick Commands
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE hotel_channel_manager;"

# Import schema
mysql -u root -p hotel_channel_manager < config/database.sql

# Set permissions
chmod -R 755 logs uploads
```

## Test Order
1. index.php (landing page)
2. test.php (basic PHP)
3. setup.php (system check)
4. debug.php (detailed info)
5. admin/simple_login.php (simple login)
6. admin/login.php (full system)

## Support
If you still get errors:
1. Check debug.php output
2. Look at server error logs
3. Verify database exists and has correct permissions
4. Make sure all files are uploaded
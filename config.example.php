<?php
/**
 * Ad Management System - Configuration
 * Copy this file to config.php and fill in your database credentials
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Site
define('SITE_NAME', 'Ad Management System');
define('BASE_URL', ''); // e.g., https://time.ytb.lol

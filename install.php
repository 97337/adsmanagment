<?php
/**
 * Database Installation Script
 * Run once to create tables and default admin user
 */
require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Admins table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Domains table (ad groups)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `domains` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `domain` VARCHAR(255) NOT NULL UNIQUE,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ads table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ads` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `domain_id` INT UNSIGNED NOT NULL,
        `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
        `alliance_name` VARCHAR(255) NOT NULL DEFAULT '',
        `alliance_account` VARCHAR(255) NOT NULL DEFAULT '',
        `ad_link` VARCHAR(1024) NOT NULL DEFAULT '',
        `ad_text` TEXT,
        `image_url` VARCHAR(1024) NOT NULL DEFAULT '',
        `image_file` VARCHAR(512) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_domain_sort` (`domain_id`, `sort_order`),
        FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insert default admin (admin / admin123)
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `admins` (`username`, `password`) VALUES (?, ?)");
    $stmt->execute(['admin', $hash]);

    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Install</title>";
    echo "<style>body{font-family:system-ui;background:#0f172a;color:#e2e8f0;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}";
    echo ".box{background:#1e293b;border-radius:16px;padding:40px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,.25)}";
    echo "h1{color:#38bdf8;margin-bottom:20px}p{margin:8px 0;font-size:16px}a{color:#38bdf8;text-decoration:none}</style></head><body>";
    echo "<div class='box'><h1>✅ Installation Complete</h1>";
    echo "<p>Database and tables created successfully.</p>";
    echo "<p>Default admin: <strong>admin</strong> / <strong>admin123</strong></p>";
    echo "<p><a href='admin/login.php'>→ Go to Admin Panel</a></p></div></body></html>";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Installation failed: " . htmlspecialchars($e->getMessage());
}

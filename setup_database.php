<?php
/**
 * VISITOR MANAGEMENT SYSTEM - DATABASE SETUP SCRIPT
 * Run this file to set up or update your database
 */

// Database configuration
$host = 'localhost';
$username = 'root'; // Change this to your MySQL username
$password = '';     // Change this to your MySQL password
$database = 'admin_visitors';

echo "<h1>Visitor Management System - Database Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .warning { color: orange; }
</style>";

try {
    // Connect to MySQL server
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p class='info'>✓ Connected to MySQL server successfully</p>";
    
    // Create database if it doesn't exist
    $createDb = "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($createDb)) {
        echo "<p class='success'>✓ Database '$database' created/verified successfully</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($database);
    
    // Create guest_submissions table
    $guestTable = "CREATE TABLE IF NOT EXISTS `guest_submissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `full_name` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `agreement` tinyint(1) NOT NULL DEFAULT 0,
        `checked_out` tinyint(1) DEFAULT 0,
        `checked_out_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_submitted_at` (`submitted_at`),
        KEY `idx_checked_out` (`checked_out`),
        KEY `idx_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($guestTable)) {
        echo "<p class='success'>✓ guest_submissions table created/updated successfully</p>";
    } else {
        throw new Exception("Error creating guest_submissions table: " . $conn->error);
    }
    
    // Create visitor_logs table
    $logsTable = "CREATE TABLE IF NOT EXISTS `visitor_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `visitor_id` int(11) NOT NULL,
        `action` enum('checkin','checkout','update') NOT NULL,
        `action_details` text DEFAULT NULL,
        `performed_by` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_visitor_id` (`visitor_id`),
        KEY `idx_action` (`action`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($logsTable)) {
        echo "<p class='success'>✓ visitor_logs table created/updated successfully</p>";
    } else {
        echo "<p class='warning'>⚠ visitor_logs table creation failed: " . $conn->error . "</p>";
    }
    
    // Create admin_users table
    $adminTable = "CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(100) NOT NULL,
        `email` varchar(255) NOT NULL,
        `password_hash` varchar(255) NOT NULL,
        `role` enum('admin','employee','supervisor') DEFAULT 'employee',
        `full_name` varchar(255) NOT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `last_login` timestamp NULL DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_username` (`username`),
        UNIQUE KEY `unique_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($adminTable)) {
        echo "<p class='success'>✓ admin_users table created/updated successfully</p>";
    } else {
        echo "<p class='warning'>⚠ admin_users table creation failed: " . $conn->error . "</p>";
    }
    
    // Insert default admin user if not exists
    $checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
    $adminExists = $checkAdmin->fetch_assoc()['count'];
    
    if ($adminExists == 0) {
        $defaultAdmin = "INSERT INTO admin_users (username, email, password_hash, role, full_name) 
                        VALUES ('admin', 'admin@visitor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator')";
        
        if ($conn->query($defaultAdmin)) {
            echo "<p class='success'>✓ Default admin user created (username: admin, password: admin123)</p>";
        } else {
            echo "<p class='warning'>⚠ Default admin user creation failed: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ Default admin user already exists</p>";
    }
    
    // Create user for the application
    $createUser = "CREATE USER IF NOT EXISTS 'admin_visitors'@'localhost' IDENTIFIED BY '123'";
    if ($conn->query($createUser)) {
        echo "<p class='success'>✓ Application user 'admin_visitors' created</p>";
    } else {
        echo "<p class='warning'>⚠ Application user creation failed: " . $conn->error . "</p>";
    }
    
    // Grant permissions
    $grantPermissions = "GRANT SELECT, INSERT, UPDATE, DELETE ON `$database`.* TO 'admin_visitors'@'localhost'";
    if ($conn->query($grantPermissions)) {
        echo "<p class='success'>✓ Permissions granted to application user</p>";
    } else {
        echo "<p class='warning'>⚠ Permission grant failed: " . $conn->error . "</p>";
    }
    
    // Flush privileges
    $conn->query("FLUSH PRIVILEGES");
    
    // Show current tables
    echo "<h2>Current Database Tables:</h2>";
    $tables = $conn->query("SHOW TABLES");
    if ($tables && $tables->num_rows > 0) {
        echo "<ul>";
        while ($table = $tables->fetch_array()) {
            echo "<li>" . $table[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // Show guest_submissions structure
    echo "<h2>guest_submissions Table Structure:</h2>";
    $structure = $conn->query("DESCRIBE guest_submissions");
    if ($structure) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 class='success'>🎉 Database setup completed successfully!</h2>";
    echo "<p class='info'>Your visitor management system is ready to use.</p>";
    echo "<p class='warning'><strong>Important:</strong> Delete this setup file after running it for security reasons.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Please check your database configuration and try again.</p>";
}

$conn->close();
?>


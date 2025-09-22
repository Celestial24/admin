<?php
// Web-based Database Update Script
// Run this through your browser to update the database

session_start();

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    die("❌ Access denied. Please login first.");
}

$host = 'localhost';
$user = 'admin_visitors';
$pass = '123';
$db = 'admin_visitors';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔄 Database Update Script</h1>";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p>");
    }
    
    echo "<p class='success'>✅ Connected to database successfully!</p>";
    
    // Step 1: Create visitor_types table
    echo "<h2>Step 1: Creating visitor_types table...</h2>";
    $sql1 = "CREATE TABLE IF NOT EXISTS `visitor_types` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `type_name` varchar(100) NOT NULL,
      `description` text DEFAULT NULL,
      `color_code` varchar(7) DEFAULT '#3B82F6',
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_type_name` (`type_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql1)) {
        echo "<p class='success'>✅ visitor_types table created successfully!</p>";
    } else {
        echo "<p class='error'>❌ Error creating visitor_types: " . $conn->error . "</p>";
    }
    
    // Step 2: Insert visitor types
    echo "<h2>Step 2: Adding visitor types...</h2>";
    $sql2 = "INSERT IGNORE INTO `visitor_types` (`type_name`, `description`, `color_code`) VALUES
    ('Business', 'Business meetings, conferences, corporate visits', '#3B82F6'),
    ('Personal', 'Personal visits, family, friends', '#10B981'),
    ('Delivery', 'Package delivery, courier services', '#F59E0B'),
    ('Maintenance', 'Repair services, maintenance work', '#EF4444'),
    ('Interview', 'Job interviews, recruitment', '#8B5CF6'),
    ('Inspection', 'Safety inspections, audits', '#06B6D4'),
    ('Other', 'Other types of visits', '#6B7280')";
    
    if ($conn->query($sql2)) {
        echo "<p class='success'>✅ Visitor types added successfully!</p>";
    } else {
        echo "<p class='error'>❌ Error adding visitor types: " . $conn->error . "</p>";
    }
    
    // Step 3: Create employees table
    echo "<h2>Step 3: Creating employees table...</h2>";
    $sql3 = "CREATE TABLE IF NOT EXISTS `employees` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` varchar(50) NOT NULL,
      `full_name` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `department` varchar(100) DEFAULT NULL,
      `position` varchar(100) DEFAULT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_employee_id` (`employee_id`),
      UNIQUE KEY `unique_email` (`email`),
      KEY `idx_department` (`department`),
      KEY `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql3)) {
        echo "<p class='success'>✅ employees table created successfully!</p>";
    } else {
        echo "<p class='error'>❌ Error creating employees: " . $conn->error . "</p>";
    }
    
    // Step 4: Insert sample employees
    echo "<h2>Step 4: Adding sample employees...</h2>";
    $sql4 = "INSERT IGNORE INTO `employees` (`employee_id`, `full_name`, `email`, `department`, `position`) VALUES
    ('EMP001', 'John Smith', 'john.smith@company.com', 'IT', 'IT Manager'),
    ('EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', 'HR', 'HR Manager'),
    ('EMP003', 'Mike Wilson', 'mike.wilson@company.com', 'Finance', 'Finance Director'),
    ('EMP004', 'Lisa Brown', 'lisa.brown@company.com', 'Operations', 'Operations Manager')";
    
    if ($conn->query($sql4)) {
        echo "<p class='success'>✅ Sample employees added successfully!</p>";
    } else {
        echo "<p class='error'>❌ Error adding employees: " . $conn->error . "</p>";
    }
    
    // Step 5: Add columns to guest_submissions
    echo "<h2>Step 5: Adding columns to guest_submissions table...</h2>";
    
    $columns = [
        "ADD COLUMN IF NOT EXISTS `visitor_type_id` int(11) DEFAULT 1",
        "ADD COLUMN IF NOT EXISTS `purpose` text DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS `company` varchar(255) DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS `host_employee` varchar(255) DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS `expected_duration` int(11) DEFAULT NULL COMMENT 'Expected duration in minutes'",
        "ADD COLUMN IF NOT EXISTS `time_in` timestamp NULL DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS `time_out` timestamp NULL DEFAULT NULL",
        "ADD COLUMN IF NOT EXISTS `actual_duration` int(11) DEFAULT NULL COMMENT 'Actual duration in minutes'",
        "ADD COLUMN IF NOT EXISTS `status` enum('Scheduled','Time In','Time Out','Completed','Cancelled') DEFAULT 'Time In'",
        "ADD COLUMN IF NOT EXISTS `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium'"
    ];
    
    foreach ($columns as $column) {
        $sql5 = "ALTER TABLE `guest_submissions` $column";
        if ($conn->query($sql5)) {
            echo "<p class='success'>✅ Added column: " . explode('`', $column)[1] . "</p>";
        } else {
            echo "<p class='error'>❌ Error adding column: " . $conn->error . "</p>";
        }
    }
    
    // Step 6: Add indexes
    echo "<h2>Step 6: Adding indexes...</h2>";
    $indexes = [
        "ADD INDEX `idx_visitor_type` (`visitor_type_id`)",
        "ADD INDEX `idx_status` (`status`)",
        "ADD INDEX `idx_priority` (`priority`)",
        "ADD INDEX `idx_time_in` (`time_in`)",
        "ADD INDEX `idx_time_out` (`time_out`)",
        "ADD INDEX `idx_company` (`company`)",
        "ADD INDEX `idx_host_employee` (`host_employee`)"
    ];
    
    foreach ($indexes as $index) {
        $sql6 = "ALTER TABLE `guest_submissions` $index";
        if ($conn->query($sql6)) {
            echo "<p class='success'>✅ Added index: " . explode('`', $index)[1] . "</p>";
        } else {
            echo "<p class='warning'>⚠️ Index may already exist: " . $conn->error . "</p>";
        }
    }
    
    // Step 7: Update existing data
    echo "<h2>Step 7: Updating existing data...</h2>";
    $sql7 = "UPDATE guest_submissions 
             SET 
                 time_in = submitted_at,
                 time_out = checked_out_at,
                 status = CASE 
                     WHEN checked_out = 1 THEN 'Time Out'
                     ELSE 'Time In'
                 END,
                 visitor_type_id = 1
             WHERE time_in IS NULL";
    
    if ($conn->query($sql7)) {
        $affected = $conn->affected_rows;
        echo "<p class='success'>✅ Updated $affected existing records!</p>";
    } else {
        echo "<p class='warning'>⚠️ No existing records to update or error: " . $conn->error . "</p>";
    }
    
    // Verification
    echo "<h2>🔍 Verification</h2>";
    
    // Check visitor types
    $result = $conn->query("SELECT COUNT(*) as count FROM visitor_types");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p class='info'>📊 Visitor types available: $count</p>";
    }
    
    // Check employees
    $result = $conn->query("SELECT COUNT(*) as count FROM employees");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p class='info'>👥 Employees available: $count</p>";
    }
    
    // Check guest_submissions columns
    $result = $conn->query("DESCRIBE guest_submissions");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = ['visitor_type_id', 'purpose', 'company', 'host_employee', 'time_in', 'time_out', 'status'];
        $foundColumns = array_intersect($requiredColumns, $columns);
        
        echo "<p class='info'>📋 Required columns found: " . count($foundColumns) . "/" . count($requiredColumns) . "</p>";
        echo "<p class='info'>✅ Found: " . implode(', ', $foundColumns) . "</p>";
    }
    
    echo "<h2 class='success'>🎉 Database Update Completed Successfully!</h2>";
    echo "<p class='success'>🚀 Your visitor management system is now ready to use!</p>";
    echo "<p><a href='../user/Visitors.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Visitor Form</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>

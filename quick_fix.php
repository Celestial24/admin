<?php
// Quick Database Fix - Add Missing Columns
// Run this to fix the "Unknown column 'visitor_type_id'" error

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
    <title>Quick Database Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Quick Database Fix</h1>
<p>This will add the missing columns to fix the 'visitor_type_id' error.</p>";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p>");
    }
    
    echo "<p class='success'>✅ Connected to database successfully!</p>";
    
    // Step 1: Add missing columns to guest_submissions
    echo "<h2>🔧 Adding Missing Columns...</h2>";
    
    $columns = [
        "visitor_type_id" => "int(11) DEFAULT 1",
        "purpose" => "text DEFAULT NULL",
        "company" => "varchar(255) DEFAULT NULL",
        "host_employee" => "varchar(255) DEFAULT NULL",
        "expected_duration" => "int(11) DEFAULT NULL",
        "time_in" => "timestamp NULL DEFAULT NULL",
        "time_out" => "timestamp NULL DEFAULT NULL",
        "actual_duration" => "int(11) DEFAULT NULL",
        "status" => "enum('Scheduled','Time In','Time Out','Completed','Cancelled') DEFAULT 'Time In'",
        "priority" => "enum('Low','Medium','High','Urgent') DEFAULT 'Medium'"
    ];
    
    $addedColumns = 0;
    foreach ($columns as $columnName => $columnDef) {
        // Check if column already exists
        $checkSql = "SHOW COLUMNS FROM guest_submissions LIKE '$columnName'";
        $result = $conn->query($checkSql);
        
        if ($result && $result->num_rows > 0) {
            echo "<p class='info'>ℹ️ Column '$columnName' already exists</p>";
        } else {
            $sql = "ALTER TABLE guest_submissions ADD COLUMN `$columnName` $columnDef";
            if ($conn->query($sql)) {
                echo "<p class='success'>✅ Added column: $columnName</p>";
                $addedColumns++;
            } else {
                echo "<p class='error'>❌ Error adding column $columnName: " . $conn->error . "</p>";
            }
        }
    }
    
    // Step 2: Create visitor_types table if it doesn't exist
    echo "<h2>📋 Creating visitor_types table...</h2>";
    
    $createVisitorTypes = "CREATE TABLE IF NOT EXISTS visitor_types (
        id int(11) NOT NULL AUTO_INCREMENT,
        type_name varchar(100) NOT NULL,
        description text DEFAULT NULL,
        color_code varchar(7) DEFAULT '#3B82F6',
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createVisitorTypes)) {
        echo "<p class='success'>✅ visitor_types table created/verified</p>";
    } else {
        echo "<p class='error'>❌ Error creating visitor_types: " . $conn->error . "</p>";
    }
    
    // Step 3: Insert visitor types
    echo "<h2>📝 Adding visitor types...</h2>";
    
    $insertTypes = "INSERT IGNORE INTO visitor_types (type_name, description, color_code) VALUES
    ('Business', 'Business meetings, conferences', '#3B82F6'),
    ('Personal', 'Personal visits, family, friends', '#10B981'),
    ('Delivery', 'Package delivery, courier services', '#F59E0B'),
    ('Maintenance', 'Repair services, maintenance work', '#EF4444'),
    ('Interview', 'Job interviews, recruitment', '#8B5CF6'),
    ('Inspection', 'Safety inspections, audits', '#06B6D4'),
    ('Other', 'Other types of visits', '#6B7280')";
    
    if ($conn->query($insertTypes)) {
        echo "<p class='success'>✅ Visitor types added successfully</p>";
    } else {
        echo "<p class='error'>❌ Error adding visitor types: " . $conn->error . "</p>";
    }
    
    // Step 4: Create employees table if it doesn't exist
    echo "<h2>👥 Creating employees table...</h2>";
    
    $createEmployees = "CREATE TABLE IF NOT EXISTS employees (
        id int(11) NOT NULL AUTO_INCREMENT,
        employee_id varchar(50) NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        department varchar(100) DEFAULT NULL,
        position varchar(100) DEFAULT NULL,
        phone varchar(20) DEFAULT NULL,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createEmployees)) {
        echo "<p class='success'>✅ employees table created/verified</p>";
    } else {
        echo "<p class='error'>❌ Error creating employees: " . $conn->error . "</p>";
    }
    
    // Step 5: Insert sample employees
    echo "<h2>👤 Adding sample employees...</h2>";
    
    $insertEmployees = "INSERT IGNORE INTO employees (employee_id, full_name, email, department, position) VALUES
    ('EMP001', 'John Smith', 'john.smith@company.com', 'IT', 'IT Manager'),
    ('EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', 'HR', 'HR Manager'),
    ('EMP003', 'Mike Wilson', 'mike.wilson@company.com', 'Finance', 'Finance Director'),
    ('EMP004', 'Lisa Brown', 'lisa.brown@company.com', 'Operations', 'Operations Manager')";
    
    if ($conn->query($insertEmployees)) {
        echo "<p class='success'>✅ Sample employees added successfully</p>";
    } else {
        echo "<p class='error'>❌ Error adding employees: " . $conn->error . "</p>";
    }
    
    // Step 6: Update existing data
    echo "<h2>🔄 Updating existing data...</h2>";
    
    $updateData = "UPDATE guest_submissions 
                   SET 
                       time_in = submitted_at,
                       time_out = checked_out_at,
                       status = CASE 
                           WHEN checked_out = 1 THEN 'Time Out'
                           ELSE 'Time In'
                       END,
                       visitor_type_id = 1
                   WHERE time_in IS NULL";
    
    if ($conn->query($updateData)) {
        $affected = $conn->affected_rows;
        echo "<p class='success'>✅ Updated $affected existing records</p>";
    } else {
        echo "<p class='warning'>⚠️ No existing records to update or error: " . $conn->error . "</p>";
    }
    
    // Final verification
    echo "<h2>✅ Verification</h2>";
    
    // Check if visitor_type_id column exists
    $result = $conn->query("SHOW COLUMNS FROM guest_submissions LIKE 'visitor_type_id'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ visitor_type_id column exists!</p>";
    } else {
        echo "<p class='error'>❌ visitor_type_id column still missing!</p>";
    }
    
    // Check visitor types count
    $result = $conn->query("SELECT COUNT(*) as count FROM visitor_types");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p class='info'>📊 Visitor types available: $count</p>";
    }
    
    // Check employees count
    $result = $conn->query("SELECT COUNT(*) as count FROM employees");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p class='info'>👥 Employees available: $count</p>";
    }
    
    echo "<h2 class='success'>🎉 Database Fix Completed!</h2>";
    echo "<p class='success'>✅ The 'visitor_type_id' error should now be resolved!</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Try submitting the visitor form again</li>";
    echo "<li>✅ Check that all dropdowns are populated</li>";
    echo "<li>✅ Verify visitor data is being saved correctly</li>";
    echo "</ul>";
    
    echo "<p>";
    echo "<a href='../user/Visitors.php' class='btn'>Test Visitor Form</a>";
    echo "<a href='../module-table/visitors.php' class='btn'>Check Admin Panel</a>";
    echo "</p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>

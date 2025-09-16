<?php
// Database setup script to create required tables
include 'sql/db.php';

echo "<h2>Database Setup - Creating Required Tables</h2>";

// Create weka_contracts table if it doesn't exist
$wekaTable = "CREATE TABLE IF NOT EXISTS `weka_contracts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) DEFAULT NULL,
    `employee_name` varchar(255) NOT NULL,
    `contract_title` varchar(255) NOT NULL,
    `contract_type` varchar(100) DEFAULT NULL,
    `risk_score` decimal(5,2) DEFAULT 0.00,
    `risk_level` enum('Low','Medium','High') DEFAULT 'Low',
    `weka_confidence` decimal(5,2) DEFAULT 0.00,
    `probability_percent` decimal(5,2) DEFAULT 0.00,
    `risk_factors` text DEFAULT NULL,
    `recommendations` text DEFAULT NULL,
    `file_path` varchar(500) DEFAULT NULL,
    `ocr_text` longtext DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `risk_level` (`risk_level`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($wekaTable)) {
    echo "<p style='color: green;'>✓ weka_contracts table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating weka_contracts table: " . $conn->error . "</p>";
}

// Create visitors table if it doesn't exist
$visitorsTable = "CREATE TABLE IF NOT EXISTS `visitors` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `visitor_name` varchar(255) NOT NULL,
    `visitor_email` varchar(255) DEFAULT NULL,
    `visitor_phone` varchar(20) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `purpose` text NOT NULL,
    `checked_in_by` varchar(255) DEFAULT NULL,
    `check_in_time` timestamp NULL DEFAULT NULL,
    `check_out_time` timestamp NULL DEFAULT NULL,
    `status` enum('Checked In','Checked Out','Pending') DEFAULT 'Pending',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    KEY `check_in_time` (`check_in_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($visitorsTable)) {
    echo "<p style='color: green;'>✓ visitors table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating visitors table: " . $conn->error . "</p>";
}

// Check if users table exists and has required columns
$usersCheck = $conn->query("SHOW TABLES LIKE 'users'");
if ($usersCheck && $usersCheck->num_rows > 0) {
    echo "<p style='color: green;'>✓ users table exists</p>";
    
    // Check if required columns exist
    $columnsCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
    if (!$columnsCheck || $columnsCheck->num_rows == 0) {
        $addDept = "ALTER TABLE users ADD COLUMN department varchar(100) DEFAULT 'General'";
        if ($conn->query($addDept)) {
            echo "<p style='color: green;'>✓ Added department column to users table</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding department column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ department column exists in users table</p>";
    }
} else {
    echo "<p style='color: red;'>✗ users table does not exist. Please create it first.</p>";
}

// Insert sample data if tables are empty
$wekaCount = $conn->query("SELECT COUNT(*) as count FROM weka_contracts")->fetch_assoc()['count'];
if ($wekaCount == 0) {
    $sampleWeka = "INSERT INTO weka_contracts (employee_name, contract_title, contract_type, risk_score, risk_level, weka_confidence, probability_percent, risk_factors, recommendations) VALUES 
    ('John Doe', 'Service Agreement', 'Service', 25.50, 'Low', 85.20, 15.30, 'Standard terms, clear language', 'Contract looks good, proceed with confidence'),
    ('Jane Smith', 'Employment Contract', 'Employment', 65.75, 'High', 92.10, 78.50, 'Complex clauses, liability terms', 'Review carefully, consider legal consultation')";
    
    if ($conn->query($sampleWeka)) {
        echo "<p style='color: green;'>✓ Sample weka_contracts data inserted</p>";
    } else {
        echo "<p style='color: red;'>✗ Error inserting sample weka data: " . $conn->error . "</p>";
    }
}

$visitorsCount = $conn->query("SELECT COUNT(*) as count FROM visitors")->fetch_assoc()['count'];
if ($visitorsCount == 0) {
    $sampleVisitors = "INSERT INTO visitors (visitor_name, visitor_email, company, purpose, checked_in_by, check_in_time, status) VALUES 
    ('Alice Johnson', 'alice@company.com', 'Tech Corp', 'Business meeting', 'John Doe', NOW(), 'Checked In'),
    ('Bob Wilson', 'bob@client.com', 'Client Inc', 'Contract discussion', 'Jane Smith', NOW(), 'Checked In')";
    
    if ($conn->query($sampleVisitors)) {
        echo "<p style='color: green;'>✓ Sample visitors data inserted</p>";
    } else {
        echo "<p style='color: red;'>✗ Error inserting sample visitors data: " . $conn->error . "</p>";
    }
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='../user/dashboard.php'>Go to User Dashboard</a></p>";
?>

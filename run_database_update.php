<?php
// Database Update Script Runner
// This script will update your database with the enhanced structure

$host = 'localhost';
$user = 'admin_visitors';
$pass = '123';
$db = 'admin_visitors';

echo "🔄 Starting Database Update...\n\n";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("❌ Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Read the enhanced database update script
    $sqlFile = 'enhanced_database_update.sql';
    if (!file_exists($sqlFile)) {
        die("❌ Enhanced database update file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        echo "🔄 Executing: " . substr($statement, 0, 50) . "...\n";
        
        if ($conn->query($statement)) {
            $successCount++;
            echo "✅ Success!\n";
        } else {
            $errorCount++;
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
    
    echo "\n📊 Update Summary:\n";
    echo "✅ Successful statements: $successCount\n";
    echo "❌ Failed statements: $errorCount\n\n";
    
    if ($errorCount === 0) {
        echo "🎉 Database update completed successfully!\n";
        echo "🚀 Your visitor management system is now ready to use!\n";
    } else {
        echo "⚠️ Some statements failed, but the core functionality should work.\n";
    }
    
    // Verify the update
    echo "\n🔍 Verifying database structure...\n";
    
    // Check if visitor_types table exists
    $result = $conn->query("SHOW TABLES LIKE 'visitor_types'");
    if ($result && $result->num_rows > 0) {
        echo "✅ visitor_types table created\n";
        
        $result = $conn->query("SELECT COUNT(*) as count FROM visitor_types");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ $count visitor types available\n";
        }
    } else {
        echo "❌ visitor_types table not found\n";
    }
    
    // Check if employees table exists
    $result = $conn->query("SHOW TABLES LIKE 'employees'");
    if ($result && $result->num_rows > 0) {
        echo "✅ employees table created\n";
        
        $result = $conn->query("SELECT COUNT(*) as count FROM employees");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ $count employees available\n";
        }
    } else {
        echo "❌ employees table not found\n";
    }
    
    // Check if guest_submissions has new columns
    $result = $conn->query("DESCRIBE guest_submissions");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = ['visitor_type_id', 'purpose', 'company', 'host_employee', 'time_in', 'time_out', 'status'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "✅ All required columns added to guest_submissions\n";
        } else {
            echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Database update process completed!\n";
?>

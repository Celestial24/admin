<?php
// Database configuration for facilities system
$host = "localhost";
$username = "admin_admin_admin";
$password = "123";
$database = "admin_facilities";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// Create facilities table
$sql = "CREATE TABLE IF NOT EXISTS facilities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(255) NOT NULL,
    facility_type VARCHAR(100) NOT NULL,
    capacity INT(11) NOT NULL,
    status ENUM('Active', 'Inactive', 'Under Maintenance') DEFAULT 'Active',
    location VARCHAR(255),
    description TEXT,
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Facilities table created successfully<br>";
} else {
    echo "Error creating facilities table: " . $conn->error . "<br>";
}

// Create reservations table
$sql = "CREATE TABLE IF NOT EXISTS reservations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_id INT(11) NOT NULL,
    reserved_by VARCHAR(255) NOT NULL,
    purpose TEXT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    employee_id INT(11),
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Reservations table created successfully<br>";
} else {
    echo "Error creating reservations table: " . $conn->error . "<br>";
}

// Create maintenance_reports table
$sql = "CREATE TABLE IF NOT EXISTS maintenance_reports (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_id INT(11) NOT NULL,
    reported_by VARCHAR(255) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Maintenance reports table created successfully<br>";
} else {
    echo "Error creating maintenance reports table: " . $conn->error . "<br>";
}

// Create user_activity table
$sql = "CREATE TABLE IF NOT EXISTS user_activity (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "User activity table created successfully<br>";
} else {
    echo "Error creating user activity table: " . $conn->error . "<br>";
}

// Create contracts table
$sql = "CREATE TABLE IF NOT EXISTS contracts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    contract_name VARCHAR(255) NOT NULL,
    contract_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Terminated') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Contracts table created successfully<br>";
} else {
    echo "Error creating contracts table: " . $conn->error . "<br>";
}

// Create visitor_logs table
$sql = "CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_email VARCHAR(255),
    visitor_phone VARCHAR(20),
    purpose TEXT,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out_time TIMESTAMP NULL,
    status ENUM('Checked In', 'Checked Out') DEFAULT 'Checked In'
)";

if ($conn->query($sql) === TRUE) {
    echo "Visitor logs table created successfully<br>";
} else {
    echo "Error creating visitor logs table: " . $conn->error . "<br>";
}

// Insert sample facilities data
$sample_facilities = [
    ['Conference Room A', 'Conference Room', 20, 'Active', '2nd Floor', 'Large conference room with projector and whiteboard', 'Projector, Whiteboard, Air Conditioning, WiFi'],
    ['Conference Room B', 'Conference Room', 15, 'Active', '2nd Floor', 'Medium conference room for smaller meetings', 'Whiteboard, Air Conditioning, WiFi'],
    ['Training Room A', 'Training Room', 30, 'Active', '3rd Floor', 'Large training room with presentation equipment', 'Projector, Screen, Air Conditioning, WiFi, Sound System'],
    ['Training Room B', 'Training Room', 25, 'Active', '3rd Floor', 'Medium training room for workshops', 'Whiteboard, Air Conditioning, WiFi'],
    ['Meeting Room 1', 'Meeting Room', 8, 'Active', '1st Floor', 'Small meeting room for team discussions', 'Whiteboard, Air Conditioning, WiFi'],
    ['Meeting Room 2', 'Meeting Room', 6, 'Active', '1st Floor', 'Intimate meeting space for small groups', 'Air Conditioning, WiFi'],
    ['Auditorium', 'Auditorium', 100, 'Active', 'Ground Floor', 'Large auditorium for presentations and events', 'Stage, Sound System, Projector, Air Conditioning, WiFi'],
    ['Recreation Area', 'Recreation Area', 50, 'Active', 'Ground Floor', 'Open recreation space for events and activities', 'Air Conditioning, WiFi, Flexible Seating']
];

$stmt = $conn->prepare("INSERT INTO facilities (facility_name, facility_type, capacity, status, location, description, amenities) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($sample_facilities as $facility) {
    $stmt->bind_param("ssissss", $facility[0], $facility[1], $facility[2], $facility[3], $facility[4], $facility[5], $facility[6]);
    $stmt->execute();
}

echo "Sample facilities data inserted successfully<br>";

// Insert sample reservations data
$sample_reservations = [
    [1, 'John Doe', 'Team meeting for project discussion', '2025-01-20 10:00:00', '2025-01-20 12:00:00', 'Approved', 1, 1],
    [2, 'Jane Smith', 'Client presentation', '2025-01-21 14:00:00', '2025-01-21 16:00:00', 'Pending', 2, 2],
    [3, 'Mike Johnson', 'Training session for new employees', '2025-01-22 09:00:00', '2025-01-22 17:00:00', 'Approved', 3, 3]
];

$stmt = $conn->prepare("INSERT INTO reservations (facility_id, reserved_by, purpose, start_time, end_time, status, employee_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($sample_reservations as $reservation) {
    $stmt->bind_param("isssssii", $reservation[0], $reservation[1], $reservation[2], $reservation[3], $reservation[4], $reservation[5], $reservation[6], $reservation[7]);
    $stmt->execute();
}

echo "Sample reservations data inserted successfully<br>";

// Insert sample maintenance reports
$sample_maintenance = [
    [1, 'John Doe', 'Electrical', 'Light flickering in the room', 'Medium', 'Open'],
    [2, 'Jane Smith', 'Cleaning', 'Room needs deep cleaning', 'Low', 'Resolved'],
    [3, 'Mike Johnson', 'Equipment', 'Projector not working properly', 'High', 'In Progress']
];

$stmt = $conn->prepare("INSERT INTO maintenance_reports (facility_id, reported_by, issue_type, description, priority, status) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($sample_maintenance as $maintenance) {
    $stmt->bind_param("isssss", $maintenance[0], $maintenance[1], $maintenance[2], $maintenance[3], $maintenance[4], $maintenance[5]);
    $stmt->execute();
}

echo "Sample maintenance reports data inserted successfully<br>";

// Insert sample user activity
$sample_activity = [
    [1, 'Login', 'User logged into the system'],
    [1, 'Reservation', 'Created a new facility reservation'],
    [2, 'Login', 'User logged into the system'],
    [2, 'Maintenance', 'Reported a maintenance issue'],
    [3, 'Login', 'User logged into the system'],
    [3, 'Reservation', 'Updated facility reservation']
];

$stmt = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, ?, ?)");

foreach ($sample_activity as $activity) {
    $stmt->bind_param("iss", $activity[0], $activity[1], $activity[2]);
    $stmt->execute();
}

echo "Sample user activity data inserted successfully<br>";

// Insert sample contracts
$sample_contracts = [
    [1, 'Employment Contract', 'Employment', '2024-01-01', '2025-12-31', 'Active'],
    [2, 'Service Agreement', 'Service', '2024-06-01', '2025-05-31', 'Active'],
    [3, 'Consulting Contract', 'Consulting', '2024-03-01', '2025-02-28', 'Active']
];

$stmt = $conn->prepare("INSERT INTO contracts (user_id, contract_name, contract_type, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($sample_contracts as $contract) {
    $stmt->bind_param("isssss", $contract[0], $contract[1], $contract[2], $contract[3], $contract[4], $contract[5]);
    $stmt->execute();
}

echo "Sample contracts data inserted successfully<br>";

// Insert sample visitor logs
$sample_visitors = [
    [1, 'Alice Johnson', 'alice@example.com', '123-456-7890', 'Business meeting', '2025-01-20 09:00:00', NULL, 'Checked In'],
    [2, 'Bob Wilson', 'bob@example.com', '987-654-3210', 'Interview', '2025-01-20 10:30:00', '2025-01-20 12:00:00', 'Checked Out'],
    [3, 'Carol Davis', 'carol@example.com', '555-123-4567', 'Training session', '2025-01-21 08:00:00', NULL, 'Checked In']
];

$stmt = $conn->prepare("INSERT INTO visitor_logs (user_id, visitor_name, visitor_email, visitor_phone, purpose, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($sample_visitors as $visitor) {
    $stmt->bind_param("isssssss", $visitor[0], $visitor[1], $visitor[2], $visitor[3], $visitor[4], $visitor[5], $visitor[6], $visitor[7]);
    $stmt->execute();
}

echo "Sample visitor logs data inserted successfully<br>";

$stmt->close();
$conn->close();

echo "<br><strong>Database setup completed successfully!</strong><br>";
echo "Database: $database<br>";
echo "Host: $host<br>";
echo "Username: $username<br>";
echo "<br>You can now access the facilities management system without errors.";
?>

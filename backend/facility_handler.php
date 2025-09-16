<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include database connection
require_once '../backend/sql/db.php';

try {
    // Validate required fields
    $required_fields = ['facility_name', 'facility_type', 'capacity', 'status'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    // Sanitize input data
    $facility_name = trim($_POST['facility_name']);
    $facility_type = trim($_POST['facility_type']);
    $capacity = intval($_POST['capacity']);
    $status = trim($_POST['status']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Additional validation
    if (strlen($facility_name) < 3) {
        echo json_encode(['success' => false, 'message' => 'Facility name must be at least 3 characters']);
        exit();
    }
    
    if ($capacity < 1) {
        echo json_encode(['success' => false, 'message' => 'Capacity must be at least 1']);
        exit();
    }
    
    if (!in_array($status, ['Active', 'Inactive', 'Under Maintenance'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }
    
    // Check if facility name already exists
    $check_sql = "SELECT id FROM facilities WHERE facility_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $facility_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Facility with this name already exists']);
        exit();
    }
    
    // Insert new facility
    $sql = "INSERT INTO facilities (facility_name, facility_type, capacity, status, notes, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user']['id'];
    $stmt->bind_param("ssisss", $facility_name, $facility_type, $capacity, $status, $notes, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Facility added successfully',
            'facility_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add facility']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Facility handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>



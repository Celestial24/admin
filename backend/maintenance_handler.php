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
    $required_fields = ['facility_id', 'description'];
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
    $facility_id = intval($_POST['facility_id']);
    $description = trim($_POST['description']);
    
    // Additional validation
    if (strlen($description) < 10) {
        echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters']);
        exit();
    }
    
    // Check if facility exists
    $facility_sql = "SELECT id, facility_name FROM facilities WHERE id = ?";
    $facility_stmt = $conn->prepare($facility_sql);
    $facility_stmt->bind_param("i", $facility_id);
    $facility_stmt->execute();
    $facility_result = $facility_stmt->get_result();
    
    if ($facility_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Facility not found']);
        exit();
    }
    
    // Insert new maintenance request
    $sql = "INSERT INTO maintenance_requests (facility_id, description, status, created_by, created_at) 
            VALUES (?, ?, 'Open', ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user']['id'];
    $stmt->bind_param("iss", $facility_id, $description, $user_id);
    
    if ($stmt->execute()) {
        // Update facility status to "Under Maintenance" if it's not already
        $update_sql = "UPDATE facilities SET status = 'Under Maintenance' WHERE id = ? AND status != 'Under Maintenance'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $facility_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Maintenance request submitted successfully',
            'request_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit maintenance request']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Maintenance handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>



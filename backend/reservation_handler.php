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
    $required_fields = ['facility_id', 'reserved_by', 'purpose', 'start_time', 'end_time'];
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
    $reserved_by = trim($_POST['reserved_by']);
    $purpose = trim($_POST['purpose']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Additional validation
    if (strlen($reserved_by) < 2) {
        echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters']);
        exit();
    }
    
    if (strlen($purpose) < 10) {
        echo json_encode(['success' => false, 'message' => 'Purpose must be at least 10 characters']);
        exit();
    }
    
    // Validate time format and logic
    $start_datetime = new DateTime($start_time);
    $end_datetime = new DateTime($end_time);
    $now = new DateTime();
    
    if ($start_datetime <= $now) {
        echo json_encode(['success' => false, 'message' => 'Start time must be in the future']);
        exit();
    }
    
    if ($end_datetime <= $start_datetime) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit();
    }
    
    // Check if facility exists and is available
    $facility_sql = "SELECT id, facility_name, status FROM facilities WHERE id = ? AND status = 'Active'";
    $facility_stmt = $conn->prepare($facility_sql);
    $facility_stmt->bind_param("i", $facility_id);
    $facility_stmt->execute();
    $facility_result = $facility_stmt->get_result();
    
    if ($facility_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Facility not found or not available']);
        exit();
    }
    
    // Check for conflicting reservations
    $conflict_sql = "SELECT id FROM reservations 
                     WHERE facility_id = ? 
                     AND status IN ('Pending', 'Approved') 
                     AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
    
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("issss", $facility_id, $start_time, $start_time, $end_time, $end_time);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();
    
    if ($conflict_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Facility is already reserved for this time period']);
        exit();
    }
    
    // Insert new reservation
    $sql = "INSERT INTO reservations (facility_id, reserved_by, purpose, start_time, end_time, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user']['id'];
    $stmt->bind_param("isssss", $facility_id, $reserved_by, $purpose, $start_time, $end_time, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation submitted successfully',
            'reservation_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit reservation']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Reservation handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>



<?php
session_start();
include '../sql/db.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }
    
    $userId = $_SESSION['user_id'] ?? $_SESSION['id'];
    
    // Get user information
    $userQuery = "SELECT id, name, email, department, role, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userRow = $userResult->fetch_assoc()) {
        // Get user's contract count
        $contractQuery = "SELECT COUNT(*) as contract_count FROM weka_contracts WHERE employee_id = ? OR employee_name = ?";
        $contractStmt = $conn->prepare($contractQuery);
        $contractStmt->bind_param("is", $userId, $userRow['name']);
        $contractStmt->execute();
        $contractResult = $contractStmt->get_result();
        $contractCount = $contractResult->fetch_assoc()['contract_count'] ?? 0;
        
        // Get user's visitor log count
        $visitorQuery = "SELECT COUNT(*) as visitor_count FROM visitors WHERE user_id = ? OR checked_in_by = ?";
        $visitorStmt = $conn->prepare($visitorQuery);
        $visitorStmt->bind_param("is", $userId, $userRow['name']);
        $visitorStmt->execute();
        $visitorResult = $visitorStmt->get_result();
        $visitorCount = $visitorResult->fetch_assoc()['visitor_count'] ?? 0;
        
        // Get department user count
        $deptQuery = "SELECT COUNT(*) as dept_count FROM users WHERE department = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("s", $userRow['department']);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        $deptCount = $deptResult->fetch_assoc()['dept_count'] ?? 0;
        
        // Get recent activity (last 7 days)
        $activityQuery = "SELECT COUNT(*) as recent_activity FROM weka_contracts WHERE (employee_id = ? OR employee_name = ?) AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->bind_param("is", $userId, $userRow['name']);
        $activityStmt->execute();
        $activityResult = $activityStmt->get_result();
        $recentActivity = $activityResult->fetch_assoc()['recent_activity'] ?? 0;
        
        $response = [
            'success' => true,
            'user' => [
                'id' => $userRow['id'],
                'name' => $userRow['name'],
                'email' => $userRow['email'],
                'department' => $userRow['department'],
                'role' => $userRow['role'],
                'created_at' => $userRow['created_at']
            ],
            'stats' => [
                'contract_count' => $contractCount,
                'visitor_count' => $visitorCount,
                'department_users' => $deptCount,
                'recent_activity' => $recentActivity
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

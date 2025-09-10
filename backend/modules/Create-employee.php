<?php
// Enable error reporting during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../sql/db.php'; // This must create $conn using mysqli

function createEmployee($conn, $name, $email, $contact_number, $team_leader_id, $department, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO employees (name, email, contact_number, team_leader_id, department, password)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // Show SQL error
    }

    // If team_leader_id is empty, use null
    $team_leader_id = $team_leader_id ?: null;
    $department = $department ?: null;

    $stmt->bind_param("ssssss", $name, $email, $contact_number, $team_leader_id, $department, $hashedPassword);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    } else {
        $error = $stmt->error;
        $stmt->close();
        die("Execute failed: " . $error); // Show exact MySQL error
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $team_leader_id = trim($_POST['team_leader_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (!$name || !$email || !$contact_number || !$password) {
        header("Location: ../../Views/Create-user.php?error=" . urlencode("Please fill in all required fields."));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../../Views/Create-user.php?error=" . urlencode("Invalid email address."));
        exit;
    }

    // Create the employee
    $newEmployeeId = createEmployee($conn, $name, $email, $contact_number, $team_leader_id, $department, $password);

    if ($newEmployeeId) {
        header("Location: ../../Views/Create-user.php?success=" . urlencode("Employee created successfully."));
        exit;
    } else {
        header("Location: ../../Views/Create-user.php?error=" . urlencode("Failed to create employee. Please try again."));
        exit;
    }
} else {
    header("Location: ../../Views/Create-user.php");
    exit;
}

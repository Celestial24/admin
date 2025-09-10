<?php
// db connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "facilities";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_type'], $_POST['id'])) {
    $delete_type = $_POST['delete_type'];
    $id = (int)$_POST['id'];
    $sql = "";

    // Prepare SQL based on type
    switch ($delete_type) {
        case 'facility':
            $sql = "DELETE FROM facilities WHERE id = $id";
            break;
        case 'reservation':
            $sql = "DELETE FROM reservations WHERE id = $id";
            break;
        case 'maintenance':
            $sql = "DELETE FROM maintenance_requests WHERE id = $id";
            break;
    }

    if (!empty($sql) && $conn->query($sql) === TRUE) {
        $message = ucfirst($delete_type) . " deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete " . $delete_type . ": " . $conn->error;
        $messageType = "error";
    }

    // Redirect back to views_facility.php with message
    header("Location: views_facility.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit();
} else {
    // Invalid access
    header("Location: views_facility.php?message=" . urlencode("Invalid delete request.") . "&type=error");
    exit();
}

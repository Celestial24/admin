<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "facilities";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_id = isset($_POST['facility_id']) ? (int)$_POST['facility_id'] : 0;
    $status = isset($_POST['status']) ? $conn->real_escape_string(trim($_POST['status'])) : '';

    if ($facility_id > 0 && $status !== '') {
        $sql = "UPDATE facilities SET status = '$status' WHERE id = $facility_id";
        if ($conn->query($sql)) {
            // Redirect back with success message or to the list page
          header("Location: ../../modules/facility.php?update=success");
            exit;
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } else {
        echo "Invalid input.";
    }
} else {
    echo "Invalid request method.";
}

$conn->close();
?>

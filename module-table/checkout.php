<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'visitor';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guest_id']) && is_numeric($_POST['guest_id'])) {
        $guest_id = (int)$_POST['guest_id'];

        $sql = "UPDATE guest_submissions SET checked_out_at = NOW() WHERE id = ? AND checked_out_at IS NULL";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param('i', $guest_id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: visitors.php");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
    } else {
        $conn->close();
        die("Invalid guest ID.");
    }
} else {
    $conn->close();
    die("Invalid request method.");
}
?>

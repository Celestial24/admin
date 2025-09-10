<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database config
$host = 'localhost';
$dbname = 'contract_legal';
$user = 'root';
$pass = '';

// Connect securely
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

// Option 1 (simple):
$sql = "SELECT id FROM contracts ORDER BY RAND() LIMIT 1";

// Option 2 (faster for large tables):
// $maxId = intval($conn->query("SELECT MAX(id) AS maxid FROM contracts")->fetch_assoc()['maxid']);
// $randomId = rand(1, $maxId);
// $sql = "SELECT id FROM contracts WHERE id >= $randomId LIMIT 1";

$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    header("Location: view_analysis.php?id=" . $row['id']);
    exit;
} else {
    http_response_code(404);
    exit('No contracts available.');
}
?>

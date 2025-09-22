<?php
$host = "localhost";
$dbname = "admin_contract";
$username = "admin_contract";
$password = "123";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
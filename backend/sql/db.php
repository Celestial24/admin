<?php
$host = "localhost";
$dbname = "admin_administrative";
$username = "admin_admin";
$password = "admin@123";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
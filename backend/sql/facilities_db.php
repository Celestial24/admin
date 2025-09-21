<?php
// Database connection for facilities system
$host = "localhost";
$username = "admin_admin_admin";
$password = "123";
$database = "admin_facilities";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>

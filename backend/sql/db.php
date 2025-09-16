<?php
$servername = "localhost";
$username = "admin_admin";
$password = "123";
$database = "admin_admin";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

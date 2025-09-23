<?php
$host = "localhost";
$dbname = "admin_facilities";
$username = "admin_facilities";
$password = "123";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
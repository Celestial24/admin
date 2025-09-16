<?php
$host = "localhost";
$dbname = "admin_admin";
$username = "admin_admin";
$password = "123";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: use associative arrays by default
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

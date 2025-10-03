<?php
// db.php - Database connection

$servername = "localhost";   // Change if your DB server is different
$username   = "root";        // Your MySQL username
$password   = "";            // Your MySQL password
$dbname     = "aidb";   // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset to avoid encoding issues
$conn->set_charset("utf8mb4");

// Use this $conn in your queries
?>

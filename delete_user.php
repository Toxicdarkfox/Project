<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Prevent deleting your own account
if ($user_id == $_SESSION['user_id']) {
    die("❌ You cannot delete your own account!");
}

$sql = "DELETE FROM users WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header("Location: manage_users.php");
    exit();
} else {
    echo "Error deleting user.";
}
?>

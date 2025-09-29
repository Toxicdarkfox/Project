<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_courses.php");
    exit();
}

$course_id = intval($_GET['id']);

$sql = "DELETE FROM courses WHERE course_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);

if ($stmt->execute()) {
    header("Location: manage_courses.php");
    exit();
} else {
    echo "Error deleting course.";
}
?>

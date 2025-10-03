<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['course_id'])) {
    die("Course not selected.");
}

$student_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']);

// Prevent duplicate registration
$stmt = $conn->prepare("SELECT * FROM course_registrations WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "⚠ You are already registered for this course.";
} else {
    $stmt = $conn->prepare("INSERT INTO course_registrations (student_id, course_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $student_id, $course_id);

    if ($stmt->execute()) {
        echo "✅ Successfully registered!";
    } else {
        echo "❌ Registration failed.";
    }
}
?>
<br><br>
<a href="student_dashboard.php">⬅ Back to Dashboard</a>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

include "db.php";

$student_id = $_SESSION['user_id'];

if (!isset($_GET['lesson_id'])) {
    die("Lesson not specified.");
}
$lesson_id = intval($_GET['lesson_id']);

// Get course_id for this lesson
$lesson = $conn->query("SELECT * FROM lessons WHERE lesson_id = $lesson_id")->fetch_assoc();
if (!$lesson) {
    die("Lesson not found.");
}
$course_id = $lesson['course_id'];

// Check if already marked complete
$check = $conn->query("SELECT * FROM lesson_completions WHERE student_id = $student_id AND lesson_id = $lesson_id");
if ($check->num_rows == 0) {
    // Mark lesson as completed
    $stmt = $conn->prepare("INSERT INTO lesson_completions (student_id, course_id, lesson_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $student_id, $course_id, $lesson_id);
    $stmt->execute();
}

// Recalculate course progress
$total_lessons = $conn->query("SELECT COUNT(*) AS total FROM lessons WHERE course_id = $course_id")->fetch_assoc()['total'];
$completed_lessons = $conn->query("SELECT COUNT(*) AS completed FROM lesson_completions WHERE student_id = $student_id AND course_id = $course_id")->fetch_assoc()['completed'];
$progress = intval(($completed_lessons / $total_lessons) * 100);

// Update or insert into course_progress
$check_progress = $conn->query("SELECT * FROM course_progress WHERE student_id = $student_id AND course_id = $course_id");
if ($check_progress->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE course_progress SET progress_percent = ?, last_updated = NOW() WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("iii", $progress, $student_id, $course_id);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO course_progress (student_id, course_id, progress_percent) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $student_id, $course_id, $progress);
    $stmt->execute();
}

// Redirect back to course view
header("Location: student_course_view.php?course_id=$course_id");
exit();
?>

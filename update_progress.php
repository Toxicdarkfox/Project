<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(403);
    exit();
}

require_once "db.php";
$student_id = $_SESSION['user_id'];

if (!isset($_GET['course_id'])) {
    http_response_code(400);
    exit();
}
$course_id = intval($_GET['course_id']);

// Count completed lessons
$completed = $conn->query("SELECT COUNT(*) AS cnt FROM lesson_completions WHERE student_id=$student_id AND course_id=$course_id")->fetch_assoc()['cnt'] ?? 0;

// Total lessons
$total = $conn->query("SELECT COUNT(*) AS cnt FROM lessons WHERE course_id=$course_id")->fetch_assoc()['cnt'] ?? 0;

// Calculate progress %
$progress_percent = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

// Update course_progress table
$stmt = $conn->prepare("INSERT INTO course_progress (student_id, course_id, progress_percent) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE progress_percent=?, last_updated=NOW()");
$stmt->bind_param("iidd", $student_id, $course_id, $progress_percent, $progress_percent);
$stmt->execute();

echo json_encode(['progress' => $progress_percent, 'completed' => $completed]);

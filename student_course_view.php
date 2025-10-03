<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// Validate course_id
if (!isset($_GET['course_id'])) {
    die("Course not selected.");
}
$course_id = intval($_GET['course_id']);

// Check enrollment
$checkEnroll = $conn->query("SELECT * FROM course_registrations WHERE student_id = $student_id AND course_id = $course_id");
if ($checkEnroll->num_rows == 0) {
    die("You are not enrolled in this course.");
}

// Fetch course info
$course = $conn->query("SELECT * FROM courses WHERE course_id = $course_id")->fetch_assoc();
if (!$course) {
    die("Course not found.");
}

// Fetch lessons
$lessons = $conn->query("SELECT * FROM lessons WHERE course_id = $course_id ORDER BY position ASC");

// Handle lesson completion
if (isset($_GET['mark_complete'])) {
    $lesson_id = intval($_GET['mark_complete']);
    $stmt = $conn->prepare("
        INSERT INTO lesson_completions (student_id, course_id, lesson_id, completed_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE completed_at = completed_at
    ");
    $stmt->bind_param("iii", $student_id, $course_id, $lesson_id);
    $stmt->execute();

    // Update course progress
    $completed_lessons = $conn->query("
        SELECT COUNT(DISTINCT lesson_id) AS cnt 
        FROM lesson_completions 
        WHERE student_id = $student_id AND course_id = $course_id
    ")->fetch_assoc()['cnt'];

    $total_lessons = $conn->query("
        SELECT COUNT(*) AS cnt 
        FROM lessons 
        WHERE course_id = $course_id
    ")->fetch_assoc()['cnt'];

    $progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;

    $stmt = $conn->prepare("
        INSERT INTO course_progress (student_id, course_id, progress_percent) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE progress_percent = ?, last_updated = NOW()
    ");
    $stmt->bind_param("iidd", $student_id, $course_id, $progress_percent, $progress_percent);
    $stmt->execute();

    header("Location: student_course_view.php?course_id=$course_id");
    exit();
}

// Fetch completed lessons
$completed_lessons_arr = [];
$res = $conn->query("SELECT lesson_id FROM lesson_completions WHERE student_id = $student_id AND course_id = $course_id");
while ($row = $res->fetch_assoc()) {
    $completed_lessons_arr[] = $row['lesson_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Course - <?= htmlspecialchars($course['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #6f4e37;
    --secondary: #a67c52;
    --accent: #d9c7b2;
    --light: #ffffff;
    --dark: #3e2f2f;
    --gray: #8b7d7d;
}

body {
    font-family: 'Montserrat', sans-serif;
    min-height: 100vh;
    background: linear-gradient(rgba(111,78,55,0.6), rgba(166,124,82,0.4)),
                url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80') no-repeat center/cover;
    background-attachment: fixed;
    color: var(--dark);
}

.container {
    margin-bottom: 60px;
}

h2 {
    color: var(--secondary);
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.card {
    border-radius: 20px;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    margin-bottom: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.35);
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    font-weight: 600;
}
.btn-primary:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
}
.btn-success {
    background-color: var(--secondary);
    border-color: var(--secondary);
    font-weight: 600;
}
.btn-success:hover {
    background-color: var(--primary);
    border-color: var(--primary);
}

.list-group-item {
    border-radius: 15px;
    margin-bottom: 8px;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
</style>
</head>
<body>
<div class="container mt-4">
    <h2>📘 <?= htmlspecialchars($course['title']) ?></h2>
    <p class="text-muted"><?= htmlspecialchars($course['description']) ?></p>
    <a href="student_dashboard.php" class="btn btn-secondary btn-sm mb-3">⬅ Back to Dashboard</a>

    <?php if ($lessons->num_rows > 0): ?>
        <?php while ($lesson = $lessons->fetch_assoc()): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><?= htmlspecialchars($lesson['title']) ?> (Lesson <?= $lesson['position'] ?>)</h5>
                    <p><?= nl2br(htmlspecialchars($lesson['content'])) ?></p>

                    <?php if (!empty($lesson['video_url'])): ?>
                        <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank" class="btn btn-primary btn-sm">▶ Watch Video</a>
                    <?php endif; ?>

                    <?php if (!in_array($lesson['lesson_id'], $completed_lessons_arr)): ?>
                        <a href="student_course_view.php?course_id=<?= $course_id ?>&mark_complete=<?= $lesson['lesson_id'] ?>" class="btn btn-success btn-sm mt-2">Mark Complete</a>
                    <?php else: ?>
                        <span class="badge bg-success mt-2">✅ Completed</span>
                    <?php endif; ?>

                    <h6 class="mt-3">📂 Lesson Files</h6>
                    <ul class="list-group">
                        <?php
                        $files = $conn->query("SELECT * FROM lesson_files WHERE lesson_id = " . $lesson['lesson_id']);
                        if ($files->num_rows > 0) {
                            while ($f = $files->fetch_assoc()) {
                                echo "<li class='list-group-item'>
                                        <a href='" . htmlspecialchars($f['file_path']) . "' target='_blank'>" . htmlspecialchars($f['file_name']) . "</a>
                                      </li>";
                            }
                        } else {
                            echo "<li class='list-group-item'>No files uploaded yet.</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-warning">No lessons found for this course.</div>
    <?php endif; ?>
</div>
</body>
</html>

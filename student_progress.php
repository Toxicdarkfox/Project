<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";
$student_id = $_SESSION['user_id'];

// Fetch registered courses
$stmt = $conn->prepare("SELECT c.course_id, c.title, c.description 
                        FROM courses c
                        JOIN course_registrations r ON c.course_id = r.course_id
                        WHERE r.student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$registered_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to get course progress
function get_course_progress($conn, $student_id, $course_id) {
    $stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE student_id=? AND course_id=?");
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['progress_percent'] ?? 0;
}

// Function to get lessons stats
function get_lesson_stats($conn, $student_id, $course_id) {
    $total = $conn->query("SELECT COUNT(*) AS cnt FROM lessons WHERE course_id=$course_id")->fetch_assoc()['cnt'];
    $completed = $conn->query("SELECT COUNT(*) AS cnt FROM lesson_completions WHERE student_id=$student_id AND course_id=$course_id")->fetch_assoc()['cnt'];
    return [$completed, $total];
}

// Function to get average quiz score
function get_avg_quiz_score($conn, $student_id, $course_id) {
    $res = $conn->query("SELECT l.lesson_id, AVG(CASE WHEN qr.answer IS NOT NULL THEN 1 ELSE 0 END) AS avg_score
                         FROM lessons l
                         LEFT JOIN quiz_responses qr ON l.lesson_id=qr.lesson_id AND qr.student_id=$student_id
                         WHERE l.course_id=$course_id
                         GROUP BY l.lesson_id");
    $scores = [];
    while ($row = $res->fetch_assoc()) {
        $scores[] = $row['avg_score'];
    }
    return !empty($scores) ? round(array_sum($scores)/count($scores)*100,2) : 0;
}

// Function to generate AI recommendation
function generate_ai_recommendation($progress, $quiz_score) {
    if ($progress < 50 || $quiz_score < 50) return "You are falling behind. Consider reviewing previous lessons.";
    if ($progress >= 50 && $quiz_score < 70) return "Focus on quizzes to improve your understanding.";
    if ($progress >= 80 && $quiz_score >= 80) return "Great job! You're on track. Keep going!";
    return "Keep progressing through your course.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Progress Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.card { margin-bottom: 20px; }
.progress { height: 20px; }
</style>
</head>
<body>
<div class="container mt-4">
<h2>📊 Your Progress Dashboard</h2>
<a href="student_dashboard.php" class="btn btn-secondary btn-sm mb-3">⬅ Back to Dashboard</a>

<?php if (!empty($registered_courses)): ?>
    <?php foreach ($registered_courses as $course): 
        $progress = get_course_progress($conn, $student_id, $course['course_id']);
        list($completed_lessons, $total_lessons) = get_lesson_stats($conn, $student_id, $course['course_id']);
        $avg_quiz = get_avg_quiz_score($conn, $student_id, $course['course_id']);
        $recommendation = generate_ai_recommendation($progress, $avg_quiz);
    ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5><?= htmlspecialchars($course['title']) ?></h5>
            <p><?= htmlspecialchars($course['description']) ?></p>

            <strong>Course Progress:</strong>
            <div class="progress mb-2">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><?= $progress ?>%</div>
            </div>

            <strong>Lesson Completion:</strong> <?= $completed_lessons ?>/<?= $total_lessons ?> lessons completed<br>
            <strong>Average Quiz Score:</strong> <?= $avg_quiz ?>%<br>

            <strong>AI Recommendation:</strong> <em><?= $recommendation ?></em><br>
            <a href="student_course_view.php?course_id=<?= $course['course_id'] ?>" class="btn btn-primary btn-sm mt-2">View Course</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-warning">You have not registered for any courses yet.</div>
<?php endif; ?>
</div>
</body>
</html>

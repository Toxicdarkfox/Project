<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";
$student_id = $_SESSION['user_id'];

// --- Fetch course progress ---
$progress_stmt = $conn->prepare("
    SELECT c.title, cp.progress_percent, cp.course_id
    FROM courses c
    JOIN course_progress cp ON c.course_id = cp.course_id
    WHERE cp.student_id = ?
");
$progress_stmt->bind_param("i", $student_id);
$progress_stmt->execute();
$course_progress = $progress_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch average quiz scores per course ---
$quiz_stmt = $conn->prepare("
    SELECT c.title, AVG(qr.score) AS avg_score
    FROM courses c
    JOIN lessons l ON c.course_id = l.course_id
    JOIN quiz_responses qr ON l.lesson_id = qr.lesson_id
    WHERE qr.student_id = ?
    GROUP BY c.course_id
");
$quiz_stmt->bind_param("i", $student_id);
$quiz_stmt->execute();
$quiz_scores = $quiz_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch lesson completion stats ---
$lesson_stmt = $conn->prepare("
    SELECT c.title, 
           COUNT(lc.lesson_id) AS completed,
           COUNT(l.lesson_id) AS total
    FROM courses c
    JOIN lessons l ON c.course_id = l.course_id
    LEFT JOIN lesson_completions lc 
        ON l.lesson_id = lc.lesson_id AND lc.student_id = ?
    GROUP BY c.course_id
");
$lesson_stmt->bind_param("i", $student_id);
$lesson_stmt->execute();
$lesson_stats = $lesson_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Calculate overall summary ---
$total_courses_completed = 0;
$total_quiz_score = 0;
$quiz_count = 0;
$total_lessons_completed = 0;
$total_lessons = 0;

foreach ($course_progress as $cp) {
    if ($cp['progress_percent'] >= 100) $total_courses_completed++;
}

foreach ($quiz_scores as $qs) {
    $total_quiz_score += $qs['avg_score'];
    $quiz_count++;
}

foreach ($lesson_stats as $ls) {
    $total_lessons_completed += $ls['completed'];
    $total_lessons += $ls['total'];
}

$overall_avg_quiz = $quiz_count ? round($total_quiz_score / $quiz_count, 2) : 0;
$overall_lesson_percent = $total_lessons ? round(($total_lessons_completed / $total_lessons) * 100, 2) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Analytics Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>📊 Analytics Dashboard - <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
    <a href="student_dashboard.php" class="btn btn-secondary btn-sm mb-3">⬅ Back to Dashboard</a>

    <!-- Overall Performance Summary -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm text-center p-3">
                <h5>Total Courses Completed</h5>
                <h3><?= $total_courses_completed ?></h3>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm text-center p-3">
                <h5>Average Quiz Score (%)</h5>
                <h3><?= $overall_avg_quiz ?>%</h3>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm text-center p-3">
                <h5>Lessons Completed (%)</h5>
                <h3><?= $overall_lesson_percent ?>%</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm p-3">
                <h5>Course Progress (%)</h5>
                <canvas id="progressChart"></canvas>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm p-3">
                <h5>Average Quiz Scores (%)</h5>
                <canvas id="quizChart"></canvas>
            </div>
        </div>
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm p-3">
                <h5>Lesson Completion</h5>
                <canvas id="lessonChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Course Progress Chart
const progressCtx = document.getElementById('progressChart').getContext('2d');
new Chart(progressCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($course_progress, 'title')) ?>,
        datasets: [{
            label: 'Progress %',
            data: <?= json_encode(array_column($course_progress, 'progress_percent')) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});

// Average Quiz Scores Chart
const quizCtx = document.getElementById('quizChart').getContext('2d');
new Chart(quizCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($quiz_scores, 'title')) ?>,
        datasets: [{
            label: 'Avg Quiz Score %',
            data: <?= json_encode(array_map(fn($q)=>round($q['avg_score'],2), $quiz_scores)) ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.4)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 2,
            fill: true
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
});

// Lesson Completion Chart
const lessonCtx = document.getElementById('lessonChart').getContext('2d');
new Chart(lessonCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($lesson_stats, 'title')) ?>,
        datasets: [
            {
                label: 'Completed Lessons',
                data: <?= json_encode(array_map(fn($l)=>intval($l['completed']), $lesson_stats)) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Total Lessons',
                data: <?= json_encode(array_map(fn($l)=>intval($l['total']), $lesson_stats)) ?>,
                backgroundColor: 'rgba(201, 203, 207, 0.6)',
                borderColor: 'rgba(201, 203, 207, 1)',
                borderWidth: 1
            }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>

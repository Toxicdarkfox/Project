<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// --- Fetch earned badges ---
$badges_stmt = $conn->prepare("
    SELECT b.name, b.description, b.icon
    FROM student_badges sb
    JOIN badges b ON sb.badge_id = b.badge_id
    WHERE sb.student_id = ?
");
$badges_stmt->bind_param("i", $student_id);
$badges_stmt->execute();
$badges = $badges_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch completed courses with certificates ---
$certificates_stmt = $conn->prepare("
    SELECT c.title, cert.file_path
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.course_id
    WHERE cert.student_id = ?
");
$certificates_stmt->bind_param("i", $student_id);
$certificates_stmt->execute();
$certificates = $certificates_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch leaderboard (top 10 students by courses completed & avg quiz score) ---
$leaderboard_sql = "
    SELECT u.user_id, u.full_name, 
           COUNT(cp.course_id) AS courses_completed, 
           IFNULL(AVG(qr.score),0) AS avg_score
    FROM users u
    LEFT JOIN course_progress cp ON u.user_id = cp.student_id
    LEFT JOIN quiz_responses qr ON u.user_id = qr.student_id
    GROUP BY u.user_id
    ORDER BY courses_completed DESC, avg_score DESC
    LIMIT 10
";
$leaderboard = $conn->query($leaderboard_sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gamification Dashboard - <?= htmlspecialchars($full_name) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .badge-icon { width: 50px; height: 50px; object-fit: cover; margin-right: 10px; }
    .leaderboard-table th, .leaderboard-table td { text-align: center; }
</style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>🎮 Gamification Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($full_name) ?></p>
    <a href="student_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Main Dashboard</a>

    <!-- Badges -->
    <h4>🏅 Your Badges</h4>
    <?php if (!empty($badges)): ?>
        <div class="d-flex flex-wrap mb-4">
            <?php foreach ($badges as $b): ?>
                <div class="card text-center m-2 p-2" style="width: 120px;">
                    <?php if (!empty($b['icon'])): ?>
                        <img src="<?= htmlspecialchars($b['icon']) ?>" alt="<?= htmlspecialchars($b['name']) ?>" class="badge-icon mx-auto">
                    <?php endif; ?>
                    <div><strong><?= htmlspecialchars($b['name']) ?></strong></div>
                    <small><?= htmlspecialchars($b['description']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have not earned any badges yet.</div>
    <?php endif; ?>

    <!-- Leaderboard -->
    <h4>🏆 Leaderboard</h4>
    <?php if (!empty($leaderboard)): ?>
        <table class="table table-striped table-bordered leaderboard-table mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Courses Completed</th>
                    <th>Average Quiz Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $index => $l): ?>
                <tr <?= $l['user_id']==$student_id ? "class='table-success'" : "" ?>>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($l['full_name']) ?></td>
                    <td><?= $l['courses_completed'] ?></td>
                    <td><?= round($l['avg_score'], 2) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Leaderboard is empty.</div>
    <?php endif; ?>

    <!-- Certificates -->
    <h4>📜 Your Certificates</h4>
    <?php if (!empty($certificates)): ?>
        <div class="list-group mb-4">
            <?php foreach ($certificates as $c): ?>
                <a href="<?= htmlspecialchars($c['file_path']) ?>" class="list-group-item list-group-item-action" target="_blank">
                    <?= htmlspecialchars($c['title']) ?> - Download Certificate
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No certificates available yet.</div>
    <?php endif; ?>
</div>
</body>
</html>

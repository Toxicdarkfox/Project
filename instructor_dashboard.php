<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$instructor_id = $_SESSION['user_id'];

// Fetch assigned courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN course_assignments ca ON c.course_id = ca.course_id
    WHERE ca.instructor_id = ?
");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses = $stmt->get_result();
$total_courses = $courses->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instructor Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* General */
body { background: #f2ebe4; font-family: 'Segoe UI', sans-serif; }

/* Sidebar */
.sidebar {
    width: 220px;
    background: #6f4e37; /* coffee brown */
    color: #fff;
    min-height: 100vh;
    transition: 0.3s;
    position: fixed;
}
.sidebar.collapsed { width: 70px; }
.sidebar a {
    display: block;
    color: #fff;
    padding: 12px 20px;
    text-decoration: none;
    transition: 0.2s;
}
.sidebar a:hover { background: #543a2e; }
.sidebar h5 { padding: 10px 20px; color: #d8c3a5; font-size: 14px; margin-top: 15px; }
.course-text { display: inline-block; }
.sidebar.collapsed .course-text { display: none; }
#toggle-btn { cursor: pointer; color: #fff; padding: 10px 15px; }

/* Main Content */
#content { margin-left: 220px; padding: 30px; transition: 0.3s; }
.sidebar.collapsed + #content { margin-left: 70px; }

/* Cards */
.card {
    border-radius: 12px;
    background: #d8c3a5; /* light coffee */
    color: #4b2e2e;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}
.card h2 { font-weight: bold; color: #4b2e2e; }

/* Buttons */
.btn-primary {
    background: #6f4e37;
    border: none;
}
.btn-primary:hover {
    background: #543a2e;
    color: #fff;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column position-fixed">
    <div class="d-flex align-items-center justify-content-between px-3 py-2">
        <h4 class="mb-0">Instructor Panel</h4>
        <span id="toggle-btn">☰</span>
    </div>
    <a href="instructor_dashboard.php">🏠 <span class="course-text">Dashboard</span></a>
    <a href="instructor_profile.php">👤 <span class="course-text">Profile</span></a>
    <h5>📘 <span class="course-text">Your Courses</span></h5>
    <?php if ($courses->num_rows > 0): ?>
        <?php foreach ($courses as $course): ?>
            <a href="view_lessons.php?course_id=<?= $course['course_id'] ?>">
                📚 <span class="course-text"><?= htmlspecialchars($course['title']) ?></span>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="px-3 text-muted">No courses assigned</p>
    <?php endif; ?>
    <a href="logout.php">🚪 <span class="course-text">Logout</span></a>
</div>

<!-- Main Content -->
<div id="content">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?> ☕</h2>
    <p class="text-muted">Overview of your courses and actions.</p>

    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <div class="card p-4 shadow-sm">
                <h5>Total Courses Assigned</h5>
                <h2><?= $total_courses ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-4 shadow-sm">
                <h5>Actions</h5>
                <a href="instructor_profile.php" class="btn btn-primary btn-sm mt-2">Edit Profile</a>
            </div>
        </div>
    </div>

    <h4 class="mt-5">📚 Your Assigned Courses</h4>
    <?php if ($courses->num_rows > 0): ?>
        <ul class="list-group">
            <?php foreach ($courses as $course): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center" style="background:#fff4e6;">
                    <?= htmlspecialchars($course['title']) ?>
                    <a href="view_lessons.php?course_id=<?= $course['course_id'] ?>" class="btn btn-primary btn-sm">View Lessons</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-warning mt-3">No courses assigned yet.</div>
    <?php endif; ?>
</div>

<script>
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('toggle-btn');
toggleBtn.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
</script>

</body>
</html>

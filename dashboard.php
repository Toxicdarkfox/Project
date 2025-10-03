<?php
// ✅ Start session only once
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// ---------- Stats for Dashboard ----------

// Count enrolled courses
$course_result = $conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE student_id=$student_id");
$enrolled_courses = $course_result->fetch_assoc()['total'] ?? 0;

// Average progress
$progress_result = $conn->query("SELECT AVG(progress_percent) AS avg_progress FROM course_progress WHERE student_id=$student_id");
$avg_progress = round($progress_result->fetch_assoc()['avg_progress'] ?? 0);

// Certificates earned
$cert_result = $conn->query("SELECT COUNT(*) AS total FROM certificates WHERE student_id=$student_id");
$certificates = $cert_result->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - <?= htmlspecialchars($_SESSION['full_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">🎓 MyLearning</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="certificates.php">Certificates</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ✅ Dashboard Content -->
<div class="container mt-5">
  <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?> 👋</h2>
  <p class="text-muted">Here’s a quick overview of your learning progress.</p>

  <div class="row mt-4">
    <!-- Enrolled Courses -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">📘 Enrolled Courses</h5>
          <p class="display-6 fw-bold"><?= $enrolled_courses ?></p>
        </div>
      </div>
    </div>

    <!-- Average Progress -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">📊 Average Progress</h5>
          <p class="display-6 fw-bold"><?= $avg_progress ?>%</p>
        </div>
      </div>
    </div>

    <!-- Certificates Earned -->
    <div class="col-md-4">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🎓 Certificates Earned</h5>
          <p class="display-6 fw-bold"><?= $certificates ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>

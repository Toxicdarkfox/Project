<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// Get all courses
$courses = $conn->query("SELECT * FROM courses")->fetch_all(MYSQLI_ASSOC);

// Get enrolled course_ids for this student
$enrolled_stmt = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id=?");
$enrolled_stmt->bind_param("i", $student_id);
$enrolled_stmt->execute();
$enrolled_ids = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$enrolled_ids = array_column($enrolled_ids, 'course_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Available Courses - <?= htmlspecialchars($_SESSION['full_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">🎓 MyLearning</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="courses.php">Courses</a></li>
        <li class="nav-item"><a class="nav-link" href="certificates.php">Certificates</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Course List -->
<div class="container mt-5">
  <h2>📘 Available Courses</h2>
  <p class="text-muted">Browse through the list of courses and enroll to start learning.</p>

  <div class="row">
    <?php if (!empty($courses)) { ?>
      <?php foreach ($courses as $course): ?>
        <div class="col-md-4 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
              <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
              <?php if (in_array($course['course_id'], $enrolled_ids)): ?>
                <a href="student_course_view.php?course_id=<?= $course['course_id'] ?>" class="btn btn-primary btn-sm">Continue</a>
              <?php else: ?>
                <a href="register_course.php?course_id=<?= $course['course_id'] ?>" class="btn btn-success btn-sm">Enroll</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php } else { ?>
      <div class="alert alert-warning">No courses available at the moment.</div>
    <?php } ?>
  </div>
</div>

</body>
</html>

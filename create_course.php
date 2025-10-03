<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO courses (title, description, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $title, $description);

        if ($stmt->execute()) {
            $message = "✅ Course created successfully!";
        } else {
            $message = "❌ Failed to create course.";
        }
    } else {
        $message = "⚠ Course title is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.container { max-width: 600px; margin-top: 50px; }
.card { padding: 20px; }
</style>
</head>
<body>
<div class="container">
    <h2>Create New Course</h2>
    <a href="manage_courses.php" class="btn btn-secondary mb-3">⬅ Back to Courses</a>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Course Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-success">✅ Create Course</button>
        </form>
    </div>
</div>
</body>
</html>

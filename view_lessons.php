<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$instructor_id = $_SESSION['user_id'];

// Validate course_id
if (!isset($_GET['course_id'])) {
    die("Course not selected.");
}
$course_id = intval($_GET['course_id']);

// Verify instructor has access to this course
$sql = "SELECT c.course_id, c.title, c.description 
        FROM courses c
        JOIN course_assignments ca ON c.course_id = ca.course_id
        WHERE ca.instructor_id = ? AND c.course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $instructor_id, $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    die("You do not have access to this course.");
}

// Fetch lessons
$lessons = $conn->query("SELECT * FROM lessons WHERE course_id = $course_id ORDER BY position ASC");

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $lesson_id = intval($_POST['lesson_id']);
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $filename = basename($_FILES['file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir);
            $filepath = $upload_dir . time() . "_" . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                $stmt = $conn->prepare("INSERT INTO lesson_files (lesson_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $lesson_id, $filename, $filepath);
                $stmt->execute();
                $success = "✅ File uploaded successfully!";
            } else {
                $error = "❌ File upload failed.";
            }
        } else {
            $error = "❌ Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX.";
        }
    }
}

// Handle file delete
if (isset($_GET['delete_file'])) {
    $file_id = intval($_GET['delete_file']);
    $res = $conn->query("SELECT file_path FROM lesson_files WHERE file_id = $file_id");
    if ($res && $file = $res->fetch_assoc()) {
        if (file_exists($file['file_path'])) unlink($file['file_path']);
        $conn->query("DELETE FROM lesson_files WHERE file_id = $file_id");
        $success = "✅ File deleted successfully!";
    }
}

// Coffee-themed card stats
$total_lessons = $conn->query("SELECT COUNT(*) as cnt FROM lessons WHERE course_id = $course_id")->fetch_assoc()['cnt'];
$total_files = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM lesson_files lf
    JOIN lessons l ON lf.lesson_id = l.lesson_id
    WHERE l.course_id = $course_id
")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lessons - <?= htmlspecialchars($course['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f3f0eb; font-family: 'Segoe UI', sans-serif; }
.sidebar {
    height: 100vh;
    background: #4b2e2e; 
    padding-top: 20px;
    position: fixed;
    width: 220px;
    color: #fff;
}
.sidebar a { color: #fff; display: block; padding: 12px 20px; text-decoration: none; }
.sidebar a:hover { background: #6f4e37; }
.sidebar h4 { text-align: center; margin-bottom: 20px; }
.content { margin-left: 220px; padding: 20px; }
.lesson-card { 
    border-radius: 12px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    padding: 20px; 
    background: #fff8f0; 
    margin-bottom: 20px; 
}
.card-stats {
    border-radius: 12px;
    color: #fff;
    padding: 20px;
    margin-bottom: 20px;
}
.card-lessons { background: #6f4e37; }
.card-files { background: #c9a66b; color: #4b2e2e; }
.input-group > input { border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
.input-group > button { border-top-right-radius: 6px; border-bottom-right-radius: 6px; }
</style>
</head>
<body>

<div class="sidebar">
    <h4>📚 Instructor</h4>
    <a href="instructor_dashboard.php">🏠 Dashboard</a>
    <a href="instructor_profile.php">👤 Profile</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">
    <h2>📘 <?= htmlspecialchars($course['title']) ?> - Lessons</h2>
    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <a href="instructor_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

    <!-- Coffee-themed stats cards -->
    <div class="d-flex flex-wrap mb-4 gap-3">
        <div class="card-stats card-stats card-lessons flex-fill text-center">
            <h5>☕ Total Lessons</h5>
            <h2><?= $total_lessons ?></h2>
        </div>
        <div class="card-stats card-files flex-fill text-center">
            <h5>📂 Total Uploaded Files</h5>
            <h2><?= $total_files ?></h2>
        </div>
    </div>

    <!-- Lesson cards -->
    <?php while ($lesson = $lessons->fetch_assoc()) { ?>
        <div class="lesson-card">
            <h5><?= htmlspecialchars($lesson['title']) ?> (Lesson <?= $lesson['position'] ?>)</h5>
            <p><?= htmlspecialchars($lesson['content']) ?></p>
            <?php if (!empty($lesson['video_url'])) { ?>
                <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank" class="btn btn-primary btn-sm">▶ Watch Video</a>
            <?php } ?>

            <!-- Upload form -->
            <form method="POST" enctype="multipart/form-data" class="mt-3">
                <input type="hidden" name="lesson_id" value="<?= $lesson['lesson_id'] ?>">
                <div class="input-group">
                    <input type="file" name="file" class="form-control" required>
                    <button type="submit" class="btn btn-success">Upload File</button>
                </div>
            </form>

            <!-- List uploaded files -->
            <h6 class="mt-3">📂 Uploaded Files</h6>
            <ul class="list-group">
            <?php
            $files = $conn->query("SELECT * FROM lesson_files WHERE lesson_id = " . $lesson['lesson_id']);
            if ($files->num_rows > 0) {
                while ($f = $files->fetch_assoc()) {
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <a href='" . htmlspecialchars($f['file_path']) . "' target='_blank'>" . htmlspecialchars($f['file_name']) . "</a>
                            <a href='view_lessons.php?course_id=$course_id&delete_file={$f['file_id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this file?')\">🗑 Delete</a>
                          </li>";
                }
            } else {
                echo "<li class='list-group-item'>No files uploaded yet.</li>";
            }
            ?>
            </ul>
        </div>
    <?php } ?>
</div>

</body>
</html>

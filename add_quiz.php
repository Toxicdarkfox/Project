<?php
session_start();
include 'db.php';

// Check instructor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($course_id) && !empty($title)) {
        $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $course_id, $title, $description);

        if ($stmt->execute()) {
            $quiz_id = $stmt->insert_id;
            header("Location: add_question.php?quiz_id=" . $quiz_id);
            exit();
        } else {
            $message = "❌ Failed to create quiz.";
        }
    } else {
        $message = "⚠ Please fill in all required fields.";
    }
}

// Fetch courses assigned to instructor
$courses_stmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN course_instructors ci ON c.course_id = ci.course_id
    WHERE ci.instructor_id = ?
");
$courses_stmt->bind_param("i", $instructor_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Create a New Quiz</h2>
    <?php if ($message): ?>
        <div class="alert alert-warning"><?= $message ?></div>
    <?php endif; ?>
    <form method="post" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Select Course</label>
            <select name="course_id" class="form-select" required>
                <option value="">-- Select Course --</option>
                <?php while($row = $courses->fetch_assoc()): ?>
                    <option value="<?= $row['course_id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Quiz Title</label>
            <input type="text" name="title" class="form-control" placeholder="Enter quiz title" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description (optional)</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create Quiz</button>
        <a href="instructor_dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>

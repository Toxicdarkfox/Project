<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_courses.php");
    exit();
}

$course_id = intval($_GET['id']);

// Fetch course
$sql = "SELECT * FROM courses WHERE course_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    echo "Course not found.";
    exit();
}

// Update course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $update_sql = "UPDATE courses SET title=?, description=? WHERE course_id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $title, $description, $course_id);

    if ($update_stmt->execute()) {
        header("Location: manage_courses.php");
        exit();
    } else {
        echo "Error updating course.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-warning text-dark">
                <h3>Edit Course</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Course Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Update Course</button>
                    <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

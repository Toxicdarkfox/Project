<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch instructors
$instructors = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'instructor'");

// Fetch courses
$courses = $conn->query("SELECT course_id, title FROM courses");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $instructor_id = intval($_POST['instructor_id']);
    $course_id = intval($_POST['course_id']);

    // Insert into assignments table
    $stmt = $conn->prepare("INSERT INTO course_assignments (instructor_id, course_id, assigned_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $instructor_id, $course_id);

    if ($stmt->execute()) {
        $success = "✅ Course successfully assigned!";
    } else {
        $error = "❌ Error: " . $conn->error;
    }
}

// Start page content
ob_start();
?>

<h2>🎯 Assign Courses</h2>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card p-4 shadow-sm bg-white">
    <form method="POST">
        <div class="mb-3">
            <label for="instructor_id" class="form-label">Select Instructor</label>
            <select name="instructor_id" id="instructor_id" class="form-select" required>
                <option value="">-- Choose Instructor --</option>
                <?php while ($row = $instructors->fetch_assoc()): ?>
                    <option value="<?= $row['user_id'] ?>"><?= htmlspecialchars($row['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="course_id" class="form-label">Select Course</label>
            <select name="course_id" id="course_id" class="form-select" required>
                <option value="">-- Choose Course --</option>
                <?php while ($row = $courses->fetch_assoc()): ?>
                    <option value="<?= $row['course_id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Assign Course</button>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'admin_layout.php';
?>

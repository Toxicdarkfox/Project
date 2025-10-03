<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch all courses
$courses = $conn->query("SELECT * FROM courses ORDER BY created_at DESC");

// Fetch lessons if a course_id is selected
$lessons = [];
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $lessons = $conn->query("SELECT * FROM lessons WHERE course_id = $course_id ORDER BY position ASC");
}

// Start page content
ob_start();
?>

<h2>📘 Manage Courses</h2>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

<!-- Courses Table -->
<div class="card p-3 shadow-sm mb-4">
    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Instructor</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($course = $courses->fetch_assoc()): ?>
            <tr>
                <td><?= $course['course_id'] ?></td>
                <td><?= htmlspecialchars($course['title']) ?></td>
                <td><?= htmlspecialchars($course['description']) ?></td>
                <td><?= htmlspecialchars($course['instructor']) ?></td>
                <td><?= $course['created_at'] ?></td>
                <td>
                    <a href="manage_courses.php?course_id=<?= $course['course_id'] ?>" class="btn btn-primary btn-sm mb-1">View Lessons</a>
                    <a href="edit_course.php?id=<?= $course['course_id'] ?>" class="btn btn-warning btn-sm mb-1">Edit</a>
                    <a href="delete_course.php?id=<?= $course['course_id'] ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Delete this course?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_GET['course_id'])): ?>
<div class="card p-3 shadow-sm">
    <h4>📄 Lessons for Course ID <?= $course_id ?></h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Video URL</th>
                <th>Content</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($lesson = $lessons->fetch_assoc()): ?>
            <tr>
                <td><?= $lesson['lesson_id'] ?></td>
                <td><?= htmlspecialchars($lesson['title']) ?></td>
                <td><a href="<?= $lesson['video_url'] ?>" target="_blank">Watch</a></td>
                <td><?= htmlspecialchars($lesson['content']) ?></td>
                <td><?= $lesson['position'] ?></td>
                <td>
                    <a href="edit_lesson.php?id=<?= $lesson['lesson_id'] ?>" class="btn btn-warning btn-sm mb-1">Edit</a>
                    <a href="delete_lesson.php?id=<?= $lesson['lesson_id'] ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Delete this lesson?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$page_content = ob_get_clean();
include 'admin_layout.php';
?>

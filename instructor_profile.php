<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit();
}
require_once "db.php";

$user_id = $_SESSION['user_id'];

// Get current instructor info
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    $update = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
    $update->bind_param("ssi", $full_name, $email, $user_id);
    $update->execute();

    $message = "Profile updated successfully!";
    $user['full_name'] = $full_name;
    $user['email'] = $email;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instructor Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f2ebe4; font-family: 'Segoe UI', sans-serif; }

/* Sidebar */
.sidebar {
    width: 220px;
    background: #6f4e37; /* coffee brown */
    color: #fff;
    min-height: 100vh;
    position: fixed;
    transition: 0.3s;
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

/* Content */
#content { margin-left: 220px; padding: 30px; transition: 0.3s; }
.sidebar.collapsed + #content { margin-left: 70px; }

/* Card */
.card {
    border-radius: 12px;
    background: #d8c3a5; /* light coffee */
    color: #4b2e2e;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.card h5 { color: #4b2e2e; font-weight: bold; }

/* Buttons */
.btn-primary {
    background: #6f4e37;
    border: none;
}
.btn-primary:hover {
    background: #543a2e;
    color: #fff;
}
.btn-secondary {
    background: #c9a66b;
    border: none;
    color: #4b2e2e;
}
.btn-secondary:hover {
    background: #b5895a;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <div class="d-flex align-items-center justify-content-between px-3 py-2">
        <h4 class="mb-0">Instructor Panel</h4>
        <span id="toggle-btn">☰</span>
    </div>
    <a href="instructor_dashboard.php">🏠 <span class="course-text">Dashboard</span></a>
    <a href="instructor_profile.php">👤 <span class="course-text">Profile</span></a>
    <a href="logout.php">🚪 <span class="course-text">Logout</span></a>
</div>

<!-- Content -->
<div id="content">
    <h2>👤 My Profile</h2>
    <p class="text-muted">Manage your personal information and update your profile.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm" style="max-width: 600px;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
            <a href="instructor_dashboard.php" class="btn btn-secondary ms-2">⬅ Back to Dashboard</a>
        </form>
    </div>
</div>

<script>
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('toggle-btn');
toggleBtn.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
</script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --coffee-primary: #6f4e37;
    --coffee-secondary: #a67c52;
    --coffee-accent: #d9c7b2;
    --coffee-dark: #3e2f2f;
}

body {
    font-family: 'Montserrat', sans-serif;
    background: #f5f2ef;
    color: var(--coffee-dark);
}

.sidebar {
    height: 100vh;
    background-color: var(--coffee-primary);
    color: white;
    padding-top: 30px;
}

.sidebar a {
    display: block;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    font-weight: 600;
}

.sidebar a:hover, .sidebar a.active {
    background-color: var(--coffee-secondary);
}

.main-content {
    padding: 30px;
}

.card {
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    margin-bottom: 20px;
    background: var(--coffee-accent);
}

.btn-primary {
    background-color: var(--coffee-primary);
    border: none;
}

.btn-primary:hover {
    background-color: var(--coffee-dark);
}
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 sidebar">
        <h4 class="text-center mb-4">Admin Panel</h4>
        <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
        <a href="manage_users.php">👤 Manage Users</a>
        <a href="manage_courses.php">📘 Manage Courses</a>
        <a href="assign_course.php">🎯 Assign Courses</a>
        <hr class="bg-light">
        <a href="logout.php" class="text-danger">🚪 Logout</a>
    </nav>

    <!-- Main Content -->
    <main class="col-md-9 col-lg-10 main-content">
        <!-- Page-specific content will go here -->
        <?php if(isset($page_content)) echo $page_content; ?>
    </main>
</div>
</div>
</body>
</html>

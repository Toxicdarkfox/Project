<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once "db.php";

$user_id = $_SESSION['user_id'];

// Get current student info
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];

    $update = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
    $update->bind_param("ssi", $full_name, $email, $user_id);
    $update->execute();

    $message = "Profile updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #6f4e37;
    --secondary: #a67c52;
    --accent: #d9c7b2;
    --light: #ffffff;
    --dark: #3e2f2f;
}

body {
    font-family: 'Montserrat', sans-serif;
    min-height: 100vh;
    background: linear-gradient(rgba(111,78,55,0.6), rgba(166,124,82,0.4)),
                url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80') no-repeat center/cover;
    background-attachment: fixed;
    color: var(--dark);
}

.container {
    max-width: 500px;
    margin-top: 40px;
    margin-bottom: 60px;
}

.back-btn {
    margin-bottom: 20px;
    display: inline-flex;
    align-items: center;
    font-weight: 600;
    background-color: var(--secondary);
    border: none;
    color: var(--light);
    border-radius: 8px;
    padding: 8px 14px;
    text-decoration: none;
    transition: all 0.3s;
}

.back-btn:hover {
    background-color: var(--primary);
    text-decoration: none;
    transform: translateY(-2px);
}

.back-btn i {
    margin-right: 6px;
}

.profile-card {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(8px);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    padding: 30px;
    transition: transform 0.2s;
}

.profile-card:hover {
    transform: translateY(-5px);
}

h2 {
    text-align: center;
    font-weight: 700;
    color: var(--secondary);
    margin-bottom: 25px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.form-label {
    font-weight: 600;
    color: var(--dark);
}

.btn-update {
    background-color: var(--primary);
    border-color: var(--primary);
    color: var(--light);
    font-weight: 600;
    width: 100%;
}

.btn-update:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
}

.alert-success {
    text-align: center;
    border-radius: 12px;
}
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container">
    <!-- Back to Dashboard -->
    <a href="student_dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="profile-card">
        <h2>👤 My Profile</h2>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= $message; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-update">Update Profile</button>
        </form>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>

<?php
session_start();
include 'db.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $role     = $conn->real_escape_string($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $insert = "INSERT INTO users (fullname, username, email, role, password) 
               VALUES ('$fullname', '$username', '$email', '$role', '$password')";

    if ($conn->query($insert) === TRUE) {
        $message = "✅ User added successfully!";
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; }
        .container { width:50%; margin:50px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0px 2px 5px rgba(0,0,0,0.1); }
        h2 { text-align:center; }
        label { display:block; margin-top:10px; }
        input, select { width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:5px; }
        button { margin-top:20px; padding:10px; width:100%; background:#007bff; color:#fff; border:none; border-radius:5px; font-size:16px; }
        button:hover { background:#0056b3; }
        .msg { text-align:center; margin:10px 0; color:#28a745; font-weight:bold; }
        a { text-decoration:none; color:#007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New User</h2>
        <?php if ($message) echo "<p class='msg'>$message</p>"; ?>
        <form method="post">
            <label>Full Name</label>
            <input type="text" name="fullname" required>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Role</label>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
                <option value="exam_officer">Exam Officer</option>
                <option value="principal">Principal</option>
            </select>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Create User</button>
        </form>
        <p style="text-align:center; margin-top:15px;">
            <a href="manage_users.php">⬅ Back to Manage Users</a>
        </p>
    </div>
</body>
</html>

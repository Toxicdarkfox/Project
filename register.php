<?php
session_start();
include 'db.php';

$message_login = "";
$message_register = "";

// LOGIN
if (isset($_POST['login'])) {
    $email    = $conn->real_escape_string($_POST['email']);
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'student') header("Location: student_dashboard.php");
        elseif ($user['role'] == 'instructor') header("Location: instructor_dashboard.php");
        else header("Location: admin_dashboard.php");
        exit();
    } else {
        $message_login = "Invalid email or password!";
    }
}

// REGISTER
if (isset($_POST['register'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email     = $conn->real_escape_string($_POST['email']);
    $password  = md5($_POST['password']);
    $role      = $_POST['role'];

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message_register = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name','$email','$password','$role')";
        if ($conn->query($sql) === TRUE) {
            $message_register = "✅ Registration successful! You can login now.";
        } else {
            $message_register = "❌ Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login/Register - E-learning Platform</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(to right, #43e97b, #38f9d7);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .auth-container {
        background: #fff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        width: 100%;
        max-width: 450px;
    }
    .nav-tabs .nav-link.active {
        background: #007bff;
        color: #fff;
        border-radius: 8px 8px 0 0;
    }
    .nav-tabs .nav-link {
        border: none;
        color: #007bff;
        font-weight: bold;
        border-radius: 8px 8px 0 0;
    }
    .tab-content {
        margin-top: 20px;
    }
    .form-control, .form-select {
        margin-bottom: 15px;
        border-radius: 8px;
    }
    .btn-submit {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
    }
    .msg {
        text-align: center;
        margin-bottom: 15px;
        color: red;
    }
</style>
</head>
<body>
<div class="auth-container">
    <ul class="nav nav-tabs" id="authTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">Register</button>
        </li>
    </ul>

    <div class="tab-content" id="authTabContent">
        <!-- LOGIN -->
        <div class="tab-pane fade show active" id="login">
            <?php if($message_login) echo "<div class='msg'>$message_login</div>"; ?>
            <form method="post">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <button type="submit" name="login" class="btn btn-primary btn-submit">Login</button>
            </form>
        </div>

        <!-- REGISTER -->
        <div class="tab-pane fade" id="register">
            <?php if($message_register) echo "<div class='msg'>$message_register</div>"; ?>
            <form method="post">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <select name="role" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <option value="student">Student</option>
                    <option value="instructor">Instructor</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="register" class="btn btn-success btn-submit">Register</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

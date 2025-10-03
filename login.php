<?php
session_start();
include 'db.php';

$active_tab = 'login';
$message_login = "";
$message_register = "";

$email_remember = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';

if (isset($_POST['login'])) {
    $email    = $conn->real_escape_string($_POST['email']);
    $password = md5($_POST['password']);
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($remember) {
            setcookie('remember_email', $email, time() + 30*24*60*60, "/");
        } else {
            setcookie('remember_email', '', time() - 3600, "/");
        }

        if ($user['role'] == 'student') header("Location: student_dashboard.php");
        elseif ($user['role'] == 'instructor') header("Location: instructor_dashboard.php");
        else header("Location: admin_dashboard.php");
        exit();
    } else {
        $message_login = "Invalid email or password!";
        $active_tab = 'login';
    }
}

if (isset($_POST['register'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email     = $conn->real_escape_string($_POST['email']);
    $password  = md5($_POST['password']);
    $role      = $_POST['role'];

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message_register = "Email already registered!";
        $active_tab = 'register';
    } else {
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name','$email','$password','$role')";
        if ($conn->query($sql) === TRUE) {
            $message_register = "✅ Registration successful! You can login now.";
            $active_tab = 'login';
        } else {
            $message_register = "❌ Error: " . $conn->error;
            $active_tab = 'register';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login/Register - E-Learning</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --primary: #6f4e37;
    --secondary: #a67c52;
    --accent: #d9c7b2;
    --light: #ffffff;
    --dark: #3e2f2f;
    --gray: #8b7d7d;
}

body {
    font-family: Arial, sans-serif;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1600&q=80') no-repeat center/cover;
    background-attachment: fixed;
    position: relative;
}

body::before {
    content: '';
    position: absolute;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5); /* dark overlay */
    z-index:0;
}

.auth-container {
    position: relative;
    z-index:1;
    background: rgba(255, 255, 255, 0.85);
    padding:40px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.3);
    width:100%;
    max-width:450px;
    backdrop-filter: blur(8px);
}

.nav-tabs .nav-link.active {
    background: var(--primary);
    color: var(--light);
    border-radius:8px 8px 0 0;
}

.nav-tabs .nav-link {
    border:none;
    color: var(--primary);
    font-weight:bold;
    border-radius:8px 8px 0 0;
}

.tab-content { margin-top:20px; }
.form-control, .form-select { margin-bottom:15px; border-radius:8px; border:1px solid var(--gray); }
.btn-submit {
    width:100%;
    padding:10px;
    border-radius:8px;
    font-weight:600;
}

.btn-primary { background: var(--primary); border:none; color: var(--light); }
.btn-primary:hover { background: var(--secondary); }

.btn-success { background: var(--secondary); border:none; color: var(--light); }
.btn-success:hover { background: var(--primary); }

.msg { text-align:center; margin-bottom:15px; color:red; }
.msg-success { color:green; }

.password-toggle { position:relative; }
.password-toggle .toggle-icon { position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; color: var(--primary); }
.form-check-label a { color: var(--primary); text-decoration:none; }
.form-check-label a:hover { text-decoration:underline; color: var(--secondary); }
</style>
</head>
<body>
<div class="auth-container">
    <ul class="nav nav-tabs" id="authTab" role="tablist">
        <li class="nav-item"><button class="nav-link <?= $active_tab=='login' ? 'active':'' ?>" data-bs-toggle="tab" data-bs-target="#login">Login</button></li>
        <li class="nav-item"><button class="nav-link <?= $active_tab=='register' ? 'active':'' ?>" data-bs-toggle="tab" data-bs-target="#register">Register</button></li>
    </ul>

    <div class="tab-content">
        <!-- LOGIN -->
        <div class="tab-pane fade <?= $active_tab=='login' ? 'show active':'' ?>" id="login">
            <?php if($message_login) echo "<div class='msg'>$message_login</div>"; ?>
            <form method="post">
                <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?= htmlspecialchars($email_remember) ?>" required>
                <div class="password-toggle">
                    <input type="password" name="password" class="form-control" placeholder="Password" id="loginPassword" required>
                    <span class="toggle-icon" onclick="togglePassword('loginPassword')">👁</span>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="remember" class="form-check-input" id="rememberMe" <?= $email_remember ? 'checked':'' ?>>
                    <label class="form-check-label" for="rememberMe">Remember Me</label>
                    <a href="forgot_password.php" class="float-end">Forgot Password?</a>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-submit">Login</button>
            </form>
        </div>

        <!-- REGISTER -->
        <div class="tab-pane fade <?= $active_tab=='register' ? 'show active':'' ?>" id="register">
            <?php if($message_register) echo "<div class='msg msg-success'>$message_register</div>"; ?>
            <form method="post">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                <div class="password-toggle">
                    <input type="password" name="password" class="form-control" placeholder="Password" id="registerPassword" required>
                    <span class="toggle-icon" onclick="togglePassword('registerPassword')">👁</span>
                </div>
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
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}
</script>
</body>
</html>

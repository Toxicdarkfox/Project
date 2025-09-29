<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);

    // Check if email exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        // Here you would normally generate a token and send reset email
        $message = "✅ If this email exists, a password reset link has been sent.";
    } else {
        $message = "✅ If this email exists, a password reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - E-learning Platform</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(to right, #43e97b, #38f9d7); height:100vh; display:flex; justify-content:center; align-items:center; }
.auth-container { background:#fff; padding:40px; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.3); width:100%; max-width:450px; }
h2 { text-align:center; margin-bottom:20px; }
.form-control { margin-bottom:15px; border-radius:8px; }
.btn-submit { width:100%; padding:10px; border-radius:8px; }
.msg { text-align:center; margin-bottom:15px; color:green; }
</style>
</head>
<body>
<div class="auth-container">
    <h2>Forgot Password</h2>
    <?php if($message) echo "<div class='msg'>$message</div>"; ?>
    <form method="post">
        <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
        <button type="submit" class="btn btn-primary btn-submit">Send Reset Link</button>
    </form>
    <p class="text-center mt-3"><a href="login.php">⬅ Back to Login</a></p>
</div>
</body>
</html>

<?php
session_start();
include 'db.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id']);
$message = "";

// Fetch user
$sql = "SELECT * FROM users WHERE id=$id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $role     = $conn->real_escape_string($_POST['role']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password'];

    $update = "UPDATE users SET fullname='$fullname', username='$username', email='$email', role='$role', password='$password' WHERE id=$id";

    if ($conn->query($update) === TRUE) {
        $message = "✅ User updated successfully!";
    } else {
        $message = "❌ Error updating user: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update User</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; }
        .container { width:50%; margin:50px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0px 2px 5px rgba(0,0,0,0.1); }
        h2 { text-align:center; }
        label { display:block; margin-top:10px; }
        input, select { width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:5px; }
        button { margin-top:20px; padding:10px; width:100%; background:#28a745; color:#fff; border:none; border-radius:5px; font-size:16px; }
        button:hover { background:#218838; }
        .msg { text-align:center; margin:10px 0; color:#007bff; font-weight:bold; }
        a { text-decoration:none; color:#007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update User (ID: <?php echo $id; ?>)</h2>
        <?php if ($message) echo "<p class='msg'>$message</p>"; ?>
        <form method="post">
            <label>Full Name</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>

            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label>Role</label>
            <select name="role" required>
                <option value="admin" <?php if ($user['role']=='admin') echo "selected"; ?>>Admin</option>
                <option value="teacher" <?php if ($user['role']=='teacher') echo "selected"; ?>>Teacher</option>
                <option value="student" <?php if ($user['role']=='student') echo "selected"; ?>>Student</option>
                <option value="exam_officer" <?php if ($user['role']=='exam_officer') echo "selected"; ?>>Exam Officer</option>
                <option value="principal" <?php if ($user['role']=='principal') echo "selected"; ?>>Principal</option>
            </select>

            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">

            <button type="submit">Update User</button>
        </form>
        <p style="text-align:center; margin-top:15px;">
            <a href="manage_users.php">⬅ Back to Manage Users</a>
        </p>
    </div>
</body>
</html>

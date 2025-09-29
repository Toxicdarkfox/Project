<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch all users
$users = $conn->query("SELECT user_id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");
?>

<?php ob_start(); ?>

<h2>👥 Manage Users</h2>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

<div class="card p-4 shadow-sm bg-white">
    <?php if ($users->num_rows > 0): ?>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= ucfirst($user['role']) ?></td>
                        <td><?= date("d M Y", strtotime($user['created_at'])) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="edit_user.php?id=<?= $user['user_id'] ?>">Edit</a>
                            <a class="btn btn-sm btn-danger" href="delete_user.php?id=<?= $user['user_id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No users found in the system.</div>
    <?php endif; ?>
</div>

<?php
$page_content = ob_get_clean();
include 'admin_layout.php';
?>

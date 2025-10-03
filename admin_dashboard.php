<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Fetch counts
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'];
$total_instructors = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='instructor'")->fetch_assoc()['cnt'];
$total_admins = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='admin'")->fetch_assoc()['cnt'];
$total_courses = $conn->query("SELECT COUNT(*) as cnt FROM courses")->fetch_assoc()['cnt'];

// Recent activities
$recent_activities = $conn->query("
    SELECT u.full_name AS name, u.role, 'Registered' AS activity, u.created_at AS activity_time
    FROM users u
    UNION
    SELECT c.title AS name, 'Course' AS role, 'Created' AS activity, c.created_at AS activity_time
    FROM courses c
    ORDER BY activity_time DESC
    LIMIT 10
");

// Start page content
ob_start();
?>

<h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?> ☕ Admin</h2>

<div class="row my-4">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Total Students</h5>
            <h3><?= $total_students ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Total Instructors</h5>
            <h3><?= $total_instructors ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Total Admins</h5>
            <h3><?= $total_admins ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Total Courses</h5>
            <h3><?= $total_courses ?></h3>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pie Chart -->
    <div class="col-md-6 mb-4">
        <div class="card p-3">
            <h5 class="card-title text-center">User Distribution</h5>
            <canvas id="userPie" style="height:300px;"></canvas>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
        <div class="card p-3">
            <h5 class="card-title">📌 Recent Activities</h5>
            <ul class="list-unstyled">
                <?php while($act = $recent_activities->fetch_assoc()):
                    $icon = ($act['activity']=='Registered') ? '👤' : '📝';
                    $color = ($act['activity']=='Registered') ? 'text-success' : 'text-primary';
                ?>
                    <li class="mb-2">
                        <span class="<?= $color ?> fw-bold"><?= $icon ?> <?= htmlspecialchars($act['name']) ?></span> 
                        <small class="text-muted">[<?= htmlspecialchars($act['role']) ?>]</small> 
                        <?= htmlspecialchars($act['activity']) ?> 
                        <span class="text-muted float-end"><?= date('d M Y H:i', strtotime($act['activity_time'])) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('userPie').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Students', 'Instructors', 'Admins'],
        datasets: [{
            data: [<?= $total_students ?>, <?= $total_instructors ?>, <?= $total_admins ?>],
            backgroundColor: ['#6f4e37','#a67c52','#3e2f2f'],
        }]
    },
    options: { 
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php
$page_content = ob_get_clean();
include 'admin_layout.php';
?>

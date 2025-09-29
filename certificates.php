<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// Fetch student certificates
$stmt = $conn->prepare("
    SELECT c.certificate_id, c.file_path, c.issued_at, co.title AS course_title 
    FROM certificates c
    JOIN courses co ON c.course_id = co.course_id
    WHERE c.student_id = ?
    ORDER BY c.issued_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Certificates</title>
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
    margin-bottom: 60px;
}

h2 {
    color: var(--secondary);
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    text-align: center;
}

.btn-back {
    background-color: var(--primary);
    border-color: var(--primary);
    color: var(--light);
    font-weight: 600;
    margin-bottom: 25px;
}
.btn-back:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
    color: var(--light);
}

.card-certificate {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(8px);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    margin-bottom: 25px;
    transition: transform 0.2s;
}

.card-certificate:hover {
    transform: translateY(-5px);
}

.card-body {
    padding: 15px 20px;
}

.card-title {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 10px;
}

.card-text {
    font-size: 0.9rem;
    color: var(--dark);
    margin-bottom: 15px;
}

.pdf-preview {
    width: 100%;
    height: 250px;
    border: none;
    border-radius: 10px;
    margin-bottom: 10px;
}

.btn-download {
    background-color: var(--secondary);
    border-color: var(--secondary);
    font-weight: 600;
}
.btn-download:hover {
    background-color: var(--primary);
    border-color: var(--primary);
}

.alert-warning {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(8px);
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    text-align: center;
}
</style>
</head>
<body>

<?php include "navbar.php"; ?> <!-- reusing your navbar -->

<div class="container mt-4">
    <a href="student_dashboard.php" class="btn btn-back">⬅ Back to Dashboard</a>
    <h2>🎓 My Certificates</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="row">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card card-certificate shadow-sm">
                        <!-- PDF preview -->
                        <iframe src="<?= htmlspecialchars($row['file_path']); ?>" class="pdf-preview"></iframe>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['course_title']); ?></h5>
                            <p class="card-text">Issued on <?= date("M d, Y", strtotime($row['issued_at'])); ?></p>
                            <a href="<?= htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-download w-100">Download</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No certificates earned yet. Complete a course to unlock one!</div>
    <?php endif; ?>
</div>

</body>
</html>

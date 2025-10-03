<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// ---------- Registered Courses ----------
$stmt = $conn->prepare("
    SELECT c.course_id, c.title, c.description 
    FROM courses c
    JOIN course_registrations r ON c.course_id = r.course_id
    WHERE r.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$registered_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---------- All Courses ----------
$all_courses = $conn->query("SELECT * FROM courses")->fetch_all(MYSQLI_ASSOC);

// ---------- AI Recommendations ----------
$recommendations = [];
if (!empty($registered_courses)) {
    $keywords = [];
    foreach ($registered_courses as $row) {
        $keywords = array_merge($keywords, explode(" ", strtolower($row['title'] . " " . $row['description'])));
    }
    foreach ($all_courses as $course) {
        $match = 0;
        foreach ($keywords as $word) {
            if (stripos($course['title'], $word) !== false || stripos($course['description'], $word) !== false) {
                $match++;
            }
        }
        if ($match > 0) $recommendations[] = $course;
    }
}

// ---------- Student Badges ----------
$badge_stmt = $conn->prepare("
    SELECT sb.student_id, sb.badge_id, sb.awarded_on, b.name, b.description, b.icon
    FROM student_badges sb
    JOIN badges b ON sb.badge_id = b.badge_id
    WHERE sb.student_id = ?
");
$badge_stmt->bind_param("i", $student_id);
$badge_stmt->execute();
$student_badges = $badge_stmt->get_result();

// ---------- Student Certificates ----------
$cert_stmt = $conn->prepare("
    SELECT c.course_id, c.title, cert.file_path
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.course_id
    WHERE cert.student_id = ?
");
$cert_stmt->bind_param("i", $student_id);
$cert_stmt->execute();
$student_certs = $cert_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - <?= htmlspecialchars($_SESSION['full_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
    font-family: 'Montserrat', sans-serif;
    min-height: 100vh;
    background: linear-gradient(rgba(111,78,55,0.6), rgba(166,124,82,0.4)),
                url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80') no-repeat center/cover;
    background-attachment: fixed;
    color: var(--dark);
}

.navbar {
    background: rgba(111,78,55,0.9);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.navbar .nav-link {
    color: var(--light) !important;
    font-weight: 600;
}

.section-title {
    margin-top: 50px;
    margin-bottom: 20px;
    font-weight: 700;
    color: var(--secondary);
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

/* Cards */
.card {
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.35);
}

/* Buttons */
.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    font-weight: 600;
}
.btn-primary:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
}
.btn-success {
    background-color: var(--secondary);
    border-color: var(--secondary);
    font-weight: 600;
}
.btn-success:hover {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* Progress Bars */
.progress-bar.bg-danger { background-color: #d9534f; }
.progress-bar.bg-warning { background-color: #f0ad4e; }
.progress-bar.bg-success { background-color: #5cb85c; }

/* Chat Box */
#chat-box {
    height: 350px;
    overflow-y: auto;
    border: 1px solid rgba(0,0,0,0.2);
    padding: 15px;
    border-radius: 20px;
    background: rgba(217,199,178,0.75);
}

/* Chat Bubbles */
.chat-bubble {
    padding: 12px 18px;
    margin: 8px 0;
    border-radius: 18px;
    max-width: 75%;
    word-wrap: break-word;
    position: relative;
}

.chat-user {
    background-color: var(--primary);
    color: var(--light);
    margin-left: auto;
    text-align: right;
}

.chat-ai {
    background-color: rgba(255,255,255,0.9);
    color: var(--dark);
    margin-right: auto;
    text-align: left;
}

.chat-time {
    font-size: 11px;
    opacity: 0.6;
    margin-top: 3px;
}

/* Badges & Certificates */
.badge-card {
    border-radius: 20px;
    padding: 20px;
    background: rgba(255,255,255,0.85);
    text-align: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    transition: transform 0.3s, box-shadow 0.3s;
}
.badge-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
}

.list-group-item {
    border-radius: 15px;
    margin-bottom: 10px;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* General Layout */
.container {
    margin-bottom: 60px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">🎓 MyLearning</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="courses.php">Courses</a></li>
        <li class=
        <li class="nav-item"><a class="nav-link" href="certificates.php">Certificates</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <!-- Registered Courses -->
    <h3 class="section-title">📖 Your Registered Courses</h3>
    <?php if (!empty($registered_courses)) { ?>
        <div class="row">
            <?php foreach ($registered_courses as $course) { 
                $prog_stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE student_id=? AND course_id=?");
                $prog_stmt->bind_param("ii", $student_id, $course['course_id']);
                $prog_stmt->execute();
                $progress = $prog_stmt->get_result()->fetch_assoc()['progress_percent'] ?? 0;

                if ($progress < 50) $bar_class = "bg-danger";
                elseif ($progress < 100) $bar_class = "bg-warning";
                else $bar_class = "bg-success";
            ?>
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
                            <div class="progress mb-2">
                                <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= $progress ?>%;">
                                    <?= $progress ?>%
                                </div>
                            </div>
                            <a href="student_course_view.php?course_id=<?= $course['course_id'] ?>" class="btn btn-primary btn-sm">View Course</a>
                            <?php if($progress >= 100): ?>
                                <a href="generate_certificate.php?course_id=<?= $course['course_id'] ?>" class="btn btn-success btn-sm">Get Certificate</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>Complete Course</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-warning">You have not registered for any courses yet.</div>
    <?php } ?>

    <!-- Recommendations -->
    <h3 class="section-title">🎯 Recommended for You</h3>
    <?php if (!empty($recommendations)) { ?>
        <div class="row">
            <?php foreach ($recommendations as $rec) { 
                $registered = false;
                foreach ($registered_courses as $reg) {
                    if ($reg['course_id'] == $rec['course_id']) $registered = true;
                }
                $progress = 0;
                if ($registered) {
                    $prog_stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE student_id=? AND course_id=?");
                    $prog_stmt->bind_param("ii", $student_id, $rec['course_id']);
                    $prog_stmt->execute();
                    $progress = $prog_stmt->get_result()->fetch_assoc()['progress_percent'] ?? 0;
                }
            ?>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <strong><?= htmlspecialchars($rec['title']) ?></strong>
                        <p><?= htmlspecialchars($rec['description']) ?></p>
                        <?php if ($registered): ?>
                            <?php if ($progress < 100): ?>
                                <a href="student_course_view.php?course_id=<?= $rec['course_id'] ?>" class="btn btn-primary btn-sm">Continue Course</a>
                            <?php else: ?>
                                <span class="text-success">✅ Completed!</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="register_course.php?course_id=<?= $rec['course_id'] ?>" class="btn btn-success btn-sm">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-info">No recommendations yet.</div>
    <?php } ?>

    <!-- Badges -->
    <h3 class="section-title">🏅 Your Badges</h3>
    <?php if($student_badges->num_rows > 0): ?>
        <div class="row">
            <?php while($b = $student_badges->fetch_assoc()): ?>
            <div class="col-md-3">
                <div class="badge-card mb-3">
                    <div class="fs-1"><?= htmlspecialchars($b['icon']) ?></div>
                    <strong><?= htmlspecialchars($b['name']) ?></strong>
                    <small class="text-muted d-block"><?= date('M d, Y', strtotime($b['awarded_on'])) ?></small>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have not earned any badges yet.</div>
    <?php endif; ?>

    <!-- Certificates -->
    <h3 class="section-title">🎓 Your Certificates</h3>
    <?php if($student_certs->num_rows > 0): ?>
        <div class="row">
            <?php while($cert = $student_certs->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span><strong><?= htmlspecialchars($cert['title']) ?></strong></span>
                        <a href="<?= htmlspecialchars($cert['file_path']) ?>" class="btn btn-primary btn-sm" target="_blank">Download</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have no certificates yet.</div>
    <?php endif; ?>

    <!-- AI Chat Assistant -->
    <div class="card shadow-sm p-3 mt-4">
        <h5>🤖 AI Chat Assistant</h5>
        <div id="chat-box"></div>
        <div class="input-group mt-2">
            <input type="text" id="chat-input" class="form-control" placeholder="Ask a question...">
            <button id="chat-send" class="btn btn-primary">Send</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const chatBox = document.getElementById("chat-box");
const chatInput = document.getElementById("chat-input");
const chatSend = document.getElementById("chat-send");

chatSend.addEventListener("click", async () => {
    const msg = chatInput.value.trim();
    if(!msg) return;
    const userDiv = document.createElement("div");
    userDiv.className = "chat-bubble chat-user";
    userDiv.textContent = msg;
    chatBox.appendChild(userDiv);
    chatInput.value = "";
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const res = await fetch("student_ai.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "message="+encodeURIComponent(msg)
        });
        const data = await res.json();
        const aiDiv = document.createElement("div");
        aiDiv.className = "chat-bubble chat-ai";
        aiDiv.textContent = data.reply || data.error || "⚠️ No response from AI.";
        chatBox.appendChild(aiDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    } catch(err) {
        const errDiv = document.createElement("div");
        errDiv.className = "chat-bubble chat-ai";
        errDiv.textContent = "⚠️ Error: "+err.message;
        chatBox.appendChild(errDiv);
    }
});
</script>
</body>
</html>

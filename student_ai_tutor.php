<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

// OpenAI API key
$api_key = "YOUR_OPENAI_API_KEY";

$student_id = $_SESSION['user_id'];

// Fetch all registered courses
$registered_courses_stmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN course_registrations r ON c.course_id = r.course_id
    WHERE r.student_id = ?
");
$registered_courses_stmt->bind_param("i", $student_id);
$registered_courses_stmt->execute();
$registered_courses = $registered_courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$lesson_contents = [];
$weak_lessons = [];

// Fetch lesson content and calculate weak areas
foreach ($registered_courses as $course) {
    $course_id = $course['course_id'];

    // Get all lessons for this course
    $stmt = $conn->prepare("SELECT lesson_id, title, content FROM lessons WHERE course_id=? ORDER BY position ASC");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $lesson_contents[$course_id] = [];
    $weak_lessons[$course_id] = [];

    while ($row = $result->fetch_assoc()) {
        $lesson_contents[$course_id][] = $row['title'] . ": " . $row['content'];

        // Check completion
        $comp_stmt = $conn->prepare("
            SELECT COUNT(*) as done 
            FROM lesson_completions 
            WHERE student_id=? AND lesson_id=?
        ");
        $comp_stmt->bind_param("ii", $student_id, $row['lesson_id']);
        $comp_stmt->execute();
        $done = $comp_stmt->get_result()->fetch_assoc()['done'] ?? 0;

        if ($done == 0) {
            $weak_lessons[$course_id][] = $row['title'];
        }
    }
}

// Generate AI recommendations highlighting weak lessons
$ai_recommendations = [];
foreach ($lesson_contents as $course_id => $lessons) {
    $lesson_text = implode("\n\n", $lessons);
    $weak_text = !empty($weak_lessons[$course_id]) ? "The student is weak in the following lessons: " . implode(", ", $weak_lessons[$course_id]) . "." : "";

    $prompt_rec = "You are an AI tutor. Based on the following lesson contents:\n$lesson_text\n$weak_text\nProvide personalized learning recommendations for the student, highlighting which lessons or topics need extra focus.";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-4",
        "messages" => [
            ["role" => "system", "content" => "You are an AI tutor."],
            ["role" => "user", "content" => $prompt_rec]
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);

    $response_rec = curl_exec($ch);
    curl_close($ch);
    $data_rec = json_decode($response_rec, true);
    $ai_recommendations[$course_id] = $data_rec['choices'][0]['message']['content'] ?? "No recommendations available.";
}

// Handle AI Q&A using lesson content only
$ai_answer = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $all_lessons_text = implode("\n\n", array_merge(...array_values($lesson_contents)));
        $prompt = "You are an AI tutor. Answer the student's question based only on the lesson contents:\n$all_lessons_text\n\nStudent asks: \"$question\"";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-4",
            "messages" => [
                ["role" => "system", "content" => "You are an AI tutor."],
                ["role" => "user", "content" => $prompt]
            ]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $ai_answer = $data['choices'][0]['message']['content'] ?? "No response from AI.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Tutor - <?= htmlspecialchars($_SESSION['full_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>🤖 AI Tutor Assistance</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
    <a href="student_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

    <h4>🎯 Personalized Recommendations by Course</h4>
    <?php foreach ($registered_courses as $course): ?>
        <h5><?= htmlspecialchars($course['title']) ?></h5>
        <div class="card mb-3 shadow-sm p-3">
            <?= nl2br(htmlspecialchars($ai_recommendations[$course['course_id']])) ?>
        </div>
    <?php endforeach; ?>

    <h4>💬 Ask a Question</h4>
    <form method="POST" class="mb-3">
        <div class="mb-2">
            <input type="text" name="question" class="form-control" placeholder="Type your question here" required>
        </div>
        <button type="submit" class="btn btn-primary">Ask AI Tutor</button>
    </form>

    <?php if (!empty($ai_answer)): ?>
        <h5>AI Response:</h5>
        <div class="card p-3 bg-white shadow-sm"><?= nl2br(htmlspecialchars($ai_answer)) ?></div>
    <?php endif; ?>
</div>
</body>
</html>

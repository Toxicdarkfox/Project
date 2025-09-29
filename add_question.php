<?php
session_start();
include 'db.php';

// Check instructor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['quiz_id'])) {
    die("Quiz not selected.");
}

$quiz_id = intval($_GET['quiz_id']);
$message = "";

// Fetch quiz info
$quiz_stmt = $conn->prepare("SELECT title FROM quizzes WHERE quiz_id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();

// Handle delete question
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM questions WHERE question_id = ? AND quiz_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $quiz_id);
    if ($del_stmt->execute()) {
        $message = "🗑 Question deleted.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];

    if (!empty($question_text) && !empty($option_a) && !empty($option_b) && !empty($correct_answer)) {
        $stmt = $conn->prepare("
            INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer);

        if ($stmt->execute()) {
            $message = "✅ Question added successfully!";
        } else {
            $message = "❌ Failed to add question.";
        }
    } else {
        $message = "⚠ Please fill in all required fields.";
    }
}

// Fetch existing questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Add Questions to Quiz: <span class="text-primary"><?= htmlspecialchars($quiz['title']) ?></span></h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="post" class="card p-4 shadow-sm mb-4">
        <div class="mb-3">
            <label class="form-label">Question</label>
            <textarea name="question_text" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Option A</label>
            <input type="text" name="option_a" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Option B</label>
            <input type="text" name="option_b" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Option C</label>
            <input type="text" name="option_c" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Option D</label>
            <input type="text" name="option_d" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Correct Answer</label>
            <select name="correct_answer" class="form-select" required>
                <option value="">-- Select Correct Answer --</option>
                <option value="A">Option A</option>
                <option value="B">Option B</option>
                <option value="C">Option C</option>
                <option value="D">Option D</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">➕ Add Question</button>
        <a href="instructor_dashboard.php" class="btn btn-secondary">Finish</a>
    </form>

    <h3>📋 Existing Questions</h3>
    <?php if ($questions->num_rows > 0): ?>
        <ul class="list-group">
            <?php while($q = $questions->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <strong><?= htmlspecialchars($q['question_text']) ?></strong><br>
                        A: <?= htmlspecialchars($q['option_a']) ?><br>
                        B: <?= htmlspecialchars($q['option_b']) ?><br>
                        C: <?= htmlspecialchars($q['option_c']) ?><br>
                        D: <?= htmlspecialchars($q['option_d']) ?><br>
                        ✅ Correct: <?= htmlspecialchars($q['correct_answer']) ?>
                    </div>
                    <a href="add_question.php?quiz_id=<?= $quiz_id ?>&delete_id=<?= $q['question_id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-info">No questions added yet.</div>
    <?php endif; ?>
</div>
</body>
</html>

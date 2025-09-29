<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id'])) {
    die("Invalid request.");
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id']);
$answers = $_POST['answer'] ?? [];

// Fetch questions and correct answers
$stmt = $conn->prepare("SELECT question_id, correct_option FROM questions WHERE quiz_id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

$total_questions = $result->num_rows;
$correct_count = 0;

while ($row = $result->fetch_assoc()) {
    $qid = $row['question_id'];
    $correct = $row['correct_option'];

    if (isset($answers[$qid]) && $answers[$qid] === $correct) {
        $correct_count++;
    }
}

// Calculate score %
$score = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100, 2) : 0;

// Save attempt in DB
$stmt = $conn->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $quiz_id, $student_id, $score);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            text-align: center;
            margin: 50px;
        }
        .result {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            display: inline-block;
        }
        .score {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            text-decoration: none;
            background: #007bff;
            color: #fff;
            border-radius: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="result">
        <h2>🎉 Quiz Completed!</h2>
        <p class="score">Your Score: <?php echo $score; ?>%</p>
        <p>Correct Answers: <?php echo $correct_count; ?> / <?php echo $total_questions; ?></p>
        <a href="student_dashboard.php" class="btn">⬅ Back to Dashboard</a>
    </div>
</body>
</html>

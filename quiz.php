<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['quiz_id'])) {
    die("Quiz not selected.");
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['quiz_id']);

// Fetch quiz info
$quiz_stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    die("Quiz not found.");
}

// Fetch quiz questions
$questions_stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$questions_stmt->bind_param("i", $quiz_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f4f6f9;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .question {
            margin-bottom: 20px;
        }
        .question h4 {
            margin: 0 0 10px;
            color: #444;
        }
        label {
            display: block;
            margin: 5px 0;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="radio"] {
            margin-right: 10px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #333;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h2>📝 <?php echo htmlspecialchars($quiz['title']); ?></h2>
    <form action="submit_quiz.php" method="POST">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <?php 
        $qnum = 1;
        while ($q = $questions->fetch_assoc()) { ?>
            <div class="question">
                <h4><?php echo $qnum++ . ". " . htmlspecialchars($q['question_text']); ?></h4>
                <label><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="A"> <?php echo htmlspecialchars($q['option_a']); ?></label>
                <label><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="B"> <?php echo htmlspecialchars($q['option_b']); ?></label>
                <label><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="C"> <?php echo htmlspecialchars($q['option_c']); ?></label>
                <label><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="D"> <?php echo htmlspecialchars($q['option_d']); ?></label>
            </div>
        <?php } ?>
        <button type="submit">Submit Quiz</button>
    </form>
    <a class="back" href="student_dashboard.php">⬅ Back to Dashboard</a>
</body>
</html>

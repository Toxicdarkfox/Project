<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$student_id = $_SESSION['user_id'];

// Validate lesson_id
if (!isset($_GET['lesson_id'])) {
    die("Lesson not selected.");
}
$lesson_id = intval($_GET['lesson_id']);

// Fetch lesson info
$lesson = $conn->query("SELECT l.*, c.course_id, c.title AS course_title 
                        FROM lessons l
                        JOIN courses c ON l.course_id = c.course_id
                        WHERE lesson_id = $lesson_id")->fetch_assoc();

if (!$lesson) {
    die("Lesson not found.");
}

$course_id = $lesson['course_id'];

// --- AI-style Quiz Generation ---
$sentences = preg_split('/[.?!]/', $lesson['content'], -1, PREG_SPLIT_NO_EMPTY);
$questions = [];
foreach ($sentences as $idx => $sentence) {
    $sentence = trim($sentence);
    if (strlen($sentence) < 20) continue;

    // Multiple choice
    $questions[] = [
        'id' => $idx + 1,
        'type' => 'mcq',
        'question' => "What does this mean: \"$sentence\"?",
        'options' => [$sentence, "Option A", "Option B", "Option C"],
        'correct' => $sentence
    ];

    // True/False
    $questions[] = [
        'id' => $idx + 100,
        'type' => 'tf',
        'question' => "The statement \"$sentence\" is correct.",
        'options' => ['True', 'False'],
        'correct' => 'True'
    ];

    // Short answer
    $questions[] = [
        'id' => $idx + 200,
        'type' => 'short',
        'question' => "Summarize this statement: \"$sentence\""
    ];
}

// Handle form submission
$score = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $total = 0;
    $correct_count = 0;

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $ans = $answers[$q_id] ?? '';

        // Save response (avoid duplicate entries)
        $stmt = $conn->prepare("INSERT INTO quiz_responses (student_id, lesson_id, question_id, answer) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE answer=?");
        $stmt->bind_param("iiiss", $student_id, $lesson_id, $q_id, $ans, $ans);
        $stmt->execute();

        // Score calculation for MCQ & TF only
        if (isset($q['correct']) && strtolower($ans) === strtolower($q['correct'])) {
            $correct_count++;
        }
        if (isset($q['correct'])) $total++;
    }

    // Calculate score %
    $score = $total > 0 ? round(($correct_count / $total) * 100, 2) : 0;

    // --- Automatic Lesson Completion ---
    $check_completion = $conn->prepare("SELECT * FROM lesson_completions WHERE student_id=? AND course_id=? AND lesson_id=?");
    $check_completion->bind_param("iii", $student_id, $course_id, $lesson_id);
    $check_completion->execute();
    $res = $check_completion->get_result();

    if ($res->num_rows === 0) {
        // Mark lesson as completed
        $stmt = $conn->prepare("INSERT INTO lesson_completions (student_id, course_id, lesson_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $student_id, $course_id, $lesson_id);
        $stmt->execute();
    }

    // Update course progress
    $completed_lessons = $conn->query("SELECT COUNT(*) AS cnt 
                                       FROM lesson_completions 
                                       WHERE student_id=$student_id AND course_id=$course_id")->fetch_assoc()['cnt'];

    $total_lessons = $conn->query("SELECT COUNT(*) AS cnt FROM lessons WHERE course_id=$course_id")->fetch_assoc()['cnt'];
    $progress_percent = round(($completed_lessons / $total_lessons) * 100, 2);

    // Insert or update course_progress
    $stmt = $conn->prepare("INSERT INTO course_progress (student_id, course_id, progress_percent) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE progress_percent=?, last_updated=NOW()");
    $stmt->bind_param("iidd", $student_id, $course_id, $progress_percent, $progress_percent);
    $stmt->execute();

    $success = "Quiz submitted! Your score: $score% | Course progress updated to $progress_percent%";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz - <?= htmlspecialchars($lesson['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>📝 Quiz - <?= htmlspecialchars($lesson['title']) ?></h2>
    <p class="text-muted">Course: <?= htmlspecialchars($lesson['course_title']) ?></p>
    <a href="student_course_view.php?course_id=<?= $lesson['course_id'] ?>" class="btn btn-secondary btn-sm mb-3">⬅ Back to Lesson</a>

    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

    <form method="POST">
        <?php foreach ($questions as $q): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <strong><?= htmlspecialchars($q['question']) ?></strong><br><br>

                <?php if ($q['type'] === 'mcq' || $q['type'] === 'tf'): ?>
                    <?php foreach ($q['options'] as $opt): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($opt) ?>" required>
                        <label class="form-check-label"><?= htmlspecialchars($opt) ?></label>
                    </div>
                    <?php endforeach; ?>
                <?php elseif ($q['type'] === 'short'): ?>
                    <textarea class="form-control" name="answers[<?= $q['id'] ?>]" rows="2" required></textarea>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success">Submit Quiz</button>
    </form>
</div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once "db.php";
require_once __DIR__ . '/vendor/autoload.php';

use OpenAI\Client;

header('Content-Type: application/json');

$input = trim($_POST['message'] ?? '');
if ($input === '') {
    echo json_encode(['error' => 'Empty message']);
    exit();
}

$student_id = $_SESSION['user_id'];

// ✅ Cache courses & badges in session
if (!isset($_SESSION['student_courses']) || !isset($_SESSION['student_badges'])) {
    $courses = [];
    $badges = [];

    // Fetch courses
    $courses_stmt = $conn->prepare("
        SELECT title 
        FROM courses c 
        JOIN course_registrations r ON c.course_id=r.course_id 
        WHERE r.student_id=?
    ");
    $courses_stmt->bind_param("i", $student_id);
    $courses_stmt->execute();
    $res = $courses_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $courses[] = $row['title'];
    }

    // Fetch badges
    $badges_stmt = $conn->prepare("
        SELECT name 
        FROM badges b 
        JOIN student_badges sb ON b.badge_id=sb.badge_id 
        WHERE sb.student_id=?
    ");
    $badges_stmt->bind_param("i", $student_id);
    $badges_stmt->execute();
    $res = $badges_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $badges[] = $row['name'];
    }

    $_SESSION['student_courses'] = $courses;
    $_SESSION['student_badges'] = $badges;
} else {
    $courses = $_SESSION['student_courses'];
    $badges = $_SESSION['student_badges'];
}

// System prompt (shortened to save tokens)
$system_prompt = "You are an AI assistant for an e-learning platform. 
Student courses: " . implode(", ", $courses) . ". 
Student badges: " . implode(", ", $badges) . ". 
Answer only questions about courses, progress, badges, or certificates.";

// ✅ Correct OpenAI PHP client usage
$client = OpenAI::client('YOUR_API_KEY');

try {
    $response = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $input]
        ],
        'max_tokens' => 200,
        'temperature' => 0.7
    ]);

    $answer = $response->choices[0]->message->content ?? "Sorry, I could not generate a response.";
    echo json_encode(['reply' => $answer]);

} catch (Exception $e) {
    $error = $e->getMessage();
    if (stripos($error, 'rate limit') !== false) {
        echo json_encode(['error' => '⚠️ Too many requests. Please wait a few seconds and try again.']);
    } elseif (stripos($error, 'api key') !== false) {
        echo json_encode(['error' => 'Invalid API key. Please check your configuration.']);
    } else {
        echo json_encode(['error' => 'OpenAI API Error: ' . $error]);
    }
}

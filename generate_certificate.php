<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

require_once "db.php";
require_once "fpdf/fpdf.php"; // ensure path to fpdf.php is correct

$student_id = $_SESSION['user_id']; 
$course_id = intval($_GET['course_id'] ?? 0);

if(!$course_id) {
    die("Course not specified.");
}

// ✅ Check course progress
$progress_stmt = $conn->prepare("
    SELECT progress_percent 
    FROM course_progress 
    WHERE student_id=? AND course_id=?
");
$progress_stmt->bind_param("ii", $student_id, $course_id);
$progress_stmt->execute();
$progress = $progress_stmt->get_result()->fetch_assoc()['progress_percent'] ?? 0;

if ($progress < 100) {
    die("You must complete 100% of the course to get the certificate. Current progress: $progress%");
}

// ✅ Check if certificate already exists
$check_stmt = $conn->prepare("SELECT file_path FROM certificates WHERE student_id=? AND course_id=?");
$check_stmt->bind_param("ii", $student_id, $course_id);
$check_stmt->execute();
$existing_cert = $check_stmt->get_result()->fetch_assoc();

if ($existing_cert) {
    echo "<script>
        alert('Certificate already generated for this course.');
        window.location.href = 'student_dashboard.php';
    </script>";
    exit();
}

// ✅ Fetch student info
$student_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id=?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_name = $student_stmt->get_result()->fetch_assoc()['full_name'] ?? 'Student';

// ✅ Fetch course info
$course_stmt = $conn->prepare("SELECT title FROM courses WHERE course_id=?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_title = $course_stmt->get_result()->fetch_assoc()['title'] ?? 'Course';

// ✅ Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,"Certificate of Completion",0,1,'C');
$pdf->Ln(10);
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,10,"This certifies that $student_name has successfully completed the course \"$course_title\".",0,'C');
$pdf->Ln(20);
$pdf->Cell(0,10,"Date: ".date('M d, Y'),0,1,'C');

$cert_path = "certificates/certificate_{$student_id}_{$course_id}.pdf";
$pdf->Output('F', $cert_path);

// ✅ Save certificate record in DB
$insert_stmt = $conn->prepare("INSERT INTO certificates (student_id, course_id, file_path) VALUES (?, ?, ?)");
$insert_stmt->bind_param("iis", $student_id, $course_id, $cert_path);
$insert_stmt->execute();

header("Location: student_dashboard.php");
exit();
?>

<!-- FILE: create_seminar.php -->
<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

// Get form data
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$date = $_POST['date'];
$time = $_POST['time'];
$capacity = intval($_POST['capacity']);

// Validate inputs
if (empty($title) || empty($date) || empty($time) || $capacity < 2 || $capacity > 5) {
    header("Location: index.php?error=invalid_seminar_data");
    exit();
}

// Validate time is after 12 PM
$hour = intval(substr($time, 0, 2));
if ($hour < 12) {
    header("Location: index.php?error=invalid_time");
    exit();
}

// Validate date is not in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    header("Location: index.php?error=past_date");
    exit();
}

// Check if a seminar already exists at this date and time
$conflict_stmt = $conn->prepare("
    SELECT COUNT(*) as count, title 
    FROM seminars 
    WHERE date = ? AND time = ?
    GROUP BY title
");
$conflict_stmt->bind_param("ss", $date, $time);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result()->fetch_assoc();

if ($conflict_result && $conflict_result['count'] > 0) {
    header("Location: index.php?error=seminar_time_conflict&conflict=" . urlencode($conflict_result['title']));
    exit();
}

// Create seminar
$stmt = $conn->prepare("INSERT INTO seminars (title, description, date, time, capacity) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $title, $description, $date, $time, $capacity);

if ($stmt->execute()) {
    header("Location: index.php?success=seminar_created");
} else {
    header("Location: index.php?error=create_failed");
}

$conn->close();
?>
<!-- check_seminar_times.php -->
<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'no date provided']);
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

$date = $_GET['date'];

// Get all seminars on this date
$stmt = $conn->prepare("
    SELECT time, title 
    FROM seminars 
    WHERE date = ?
    ORDER BY time
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_times = [];
while ($row = $result->fetch_assoc()) {
    $booked_times[$row['time']] = $row['title'];
}

echo json_encode(['booked_times' => $booked_times]);
$conn->close();
?>
<?php
// Handle booking cancellation via email token

// Require cancellation token
if (!isset($_GET['token'])) {
    header("Location: index.php?error=invalid_token");
    exit();
}

$token = $_GET['token'];

include_once 'db.php';
$conn = get_db_connection();

// Validate token and get booking details
$stmt = $conn->prepare("
    SELECT b.id, b.user_id, b.seminar_id, b.status, b.contact_email,
           s.title, s.date, s.time
    FROM bookings b
    JOIN seminars s ON b.seminar_id = s.id
    WHERE b.cancellation_token = ? AND b.status != 'canceled'
");
$stmt->bind_param("s", $token);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

// Invalid or already cancelled
if (!$booking) {
    header("Location: index.php?error=invalid_token");
    exit();
}

// Store booking details for processing
$was_booked = $booking['status'] == 'booked';
$seminar_id = $booking['seminar_id'];
$user_id = $booking['user_id'];
$contact_email = $booking['contact_email'];

// Cancel the booking
$cancel_stmt = $conn->prepare("UPDATE bookings SET status='canceled' WHERE cancellation_token = ?");
$cancel_stmt->bind_param("s", $token);
$cancel_stmt->execute();

// Handle waitlist promotion if spot freed
$promoted_user = null;
if ($was_booked) {
    // Get first waitlisted user
    $promote_stmt = $conn->prepare("
        SELECT id, user_id, contact_email FROM bookings 
        WHERE seminar_id = ? AND status = 'waitlisted' 
        ORDER BY booking_date ASC 
        LIMIT 1
    ");
    $promote_stmt->bind_param("i", $seminar_id);
    $promote_stmt->execute();
    $waitlist_booking = $promote_stmt->get_result()->fetch_assoc();
    
    if ($waitlist_booking) {
        // Promote to booked status
        $update_stmt = $conn->prepare("UPDATE bookings SET status='booked' WHERE id=?");
        $update_stmt->bind_param("i", $waitlist_booking['id']);
        $update_stmt->execute();
        
        $promoted_user = $waitlist_booking;
    }
}

// Send cancellation confirmation
include_once 'send_email.php';
send_email($user_id, $seminar_id, 'canceled', $contact_email);

// Notify promoted user if applicable
if ($promoted_user) {
    send_email($promoted_user['user_id'], $seminar_id, 'promoted', $promoted_user['contact_email']);
}

$conn->close();

// Success redirect
header("Location: index.php?success=canceled");
exit();
?>
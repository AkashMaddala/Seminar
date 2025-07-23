<!-- actions.php -->
<?php
include_once 'db.php';
session_start();

// Redirect if no action specified
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    header("Location: index.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'];

// Handle seminar booking
if ($action == 'book') {
    // Require login
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth.php?action=login");
        exit();
    }

    // Validate required fields
    if (!isset($_POST['seminar_id']) || !isset($_POST['contact_email'])) {
        header("Location: index.php?error=missing_data");
        exit();
    }

    // Sanitize and validate input
    $seminar_id = filter_input(INPUT_POST, 'seminar_id', FILTER_VALIDATE_INT);
    $contact_email = trim($_POST['contact_email']);

    if (!$seminar_id || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=invalid_data");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $conn = get_db_connection();
    
    // Get seminar details
    $seminar_stmt = $conn->prepare("SELECT date, time FROM seminars WHERE id = ?");
    $seminar_stmt->bind_param("i", $seminar_id);
    $seminar_stmt->execute();
    $seminar_result = $seminar_stmt->get_result();
    
    if ($seminar_result->num_rows == 0) {
        header("Location: index.php?error=seminar_not_found");
        exit();
    }
    
    $seminar_data = $seminar_result->fetch_assoc();
    $seminar_date = $seminar_data['date'];
    $seminar_time = $seminar_data['time'];
    
    // Check for duplicate bookings (same seminar)
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE user_id = ? 
        AND seminar_id = ? 
        AND status != 'canceled'
    ");
    $check_stmt->bind_param("ii", $user_id, $seminar_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->fetch_assoc()['count'] > 0) {
        header("Location: index.php?error=already_booked");
        exit();
    }

    // Check for time conflicts with other seminars
    $conflict_stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b
        JOIN seminars s ON b.seminar_id = s.id
        WHERE b.user_id = ? 
        AND s.date = ?
        AND s.time = ?
        AND b.status != 'canceled'
        AND b.seminar_id != ?
    ");
    $conflict_stmt->bind_param("issi", $user_id, $seminar_date, $seminar_time, $seminar_id);
    $conflict_stmt->execute();
    
    if ($conflict_stmt->get_result()->fetch_assoc()['count'] > 0) {
        header("Location: index.php?error=time_conflict");
        exit();
    }

    // Check seminar capacity
    $capacity_stmt = $conn->prepare("
        SELECT s.capacity, 
               (SELECT COUNT(*) 
                FROM bookings 
                WHERE seminar_id = ? 
                AND status = 'booked') as booked_count
        FROM seminars s 
        WHERE s.id = ?
    ");
    $capacity_stmt->bind_param("ii", $seminar_id, $seminar_id);
    $capacity_stmt->execute();
    $capacity_data = $capacity_stmt->get_result()->fetch_assoc();
    
    if (!$capacity_data) {
        header("Location: index.php?error=seminar_not_found");
        exit();
    }

    // Auto-waitlist if full
    $status = ($capacity_data['booked_count'] >= $capacity_data['capacity']) ? 'waitlisted' : 'booked';
    
    // Generate unique token for email cancellation
    $cancellation_token = bin2hex(random_bytes(32));

    // Create booking with seminar's time
    $booking = $conn->prepare("
        INSERT INTO bookings (user_id, seminar_id, status, time_slot, contact_email, cancellation_token) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $booking->bind_param("iissss", $user_id, $seminar_id, $status, $seminar_time, $contact_email, $cancellation_token);
    $booking->execute();

    // Send confirmation email
    include_once 'send_email.php';
    send_email($user_id, $seminar_id, $status, $contact_email, $cancellation_token);

    header("Location: index.php?success=" . $status);
    exit();
    
// Handle booking cancellation
} elseif ($action == 'cancel') {
    // Require login and booking ID
    if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
        header("Location: " . (!isset($_SESSION['user_id']) ? "auth.php?action=login" : "index.php"));
        exit();
    }

    $booking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$booking_id) {
        header("Location: index.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $conn = get_db_connection();
    
    // Verify ownership and get booking details
    $verify_stmt = $conn->prepare("SELECT seminar_id, status, contact_email FROM bookings WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $booking_data = $verify_stmt->get_result()->fetch_assoc();
    
    if (!$booking_data) {
        header("Location: index.php?error=invalid_booking");
        exit();
    }

    $was_booked = $booking_data['status'] == 'booked';
    $seminar_id = $booking_data['seminar_id'];
    $contact_email = $booking_data['contact_email'];

    // Update booking status
    $cancel_stmt = $conn->prepare("UPDATE bookings SET status='canceled' WHERE id=? AND user_id=?");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);
    $cancel_stmt->execute();

    // Promote waitlisted user if spot opened
    if ($was_booked) {
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
            // Update first waitlisted to booked
            $update_stmt = $conn->prepare("UPDATE bookings SET status='booked' WHERE id=?");
            $update_stmt->bind_param("i", $waitlist_booking['id']);
            $update_stmt->execute();
            
            // Notify promoted user
            include_once 'send_email.php';
            send_email($waitlist_booking['user_id'], $seminar_id, 'promoted', $waitlist_booking['contact_email']);
        }
    }

    // Send cancellation confirmation
    include_once 'send_email.php';
    send_email($user_id, $seminar_id, 'canceled', $contact_email);

    header("Location: index.php?success=canceled");
    exit();
    
// Handle logout
} elseif ($action == 'logout') {
    session_unset();
    session_destroy();
    header("Location: auth.php?action=login&message=logged_out");
    exit();
} else {
    // Invalid action
    header("Location: index.php");
    exit();
}
?>
<!-- check_bookings.php -->
<?php
// Check bookings by email address (for guests)

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    echo '<div class="error">Invalid request.</div>';
    exit();
}

// Validate email format
$email = trim($_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo '<div class="error">Please enter a valid email address.</div>';
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

// Find active bookings for email
$query = "
    SELECT b.id, b.status, b.time_slot, b.booking_date, b.cancellation_token,
           s.title, s.description, s.date as seminar_date, s.time as seminar_time
    FROM bookings b
    JOIN seminars s ON b.seminar_id = s.id
    WHERE b.contact_email = ? AND b.status != 'canceled'
    ORDER BY s.date ASC, b.time_slot ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="bookings-results">';
    echo '<h4>Bookings for ' . htmlspecialchars($email) . '</h4>';
    
    // Display each booking
    while ($booking = $result->fetch_assoc()) {
        echo '<div class="booking-result-card">';
        echo '<h5>' . htmlspecialchars($booking['title']) . '</h5>';
        echo '<p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($booking['seminar_date'])) . '</p>';
        
        // Show the actual booked time slot
        if ($booking['time_slot']) {
            $end_time = date('g:i A', strtotime($booking['time_slot'] . ' +1 hour'));
            echo '<p><strong>Time Slot:</strong> ' . date('g:i A', strtotime($booking['time_slot'])) . ' - ' . $end_time . '</p>';
        } else {
            echo '<p><strong>Time:</strong> ' . date('g:i A', strtotime($booking['seminar_time'])) . '</p>';
        }
        
        // Status badge
        echo '<p><strong>Status:</strong> <span class="status-' . $booking['status'] . '">' . ucfirst($booking['status']) . '</span></p>';
        echo '<p><strong>Booked on:</strong> ' . date('M j, Y g:i A', strtotime($booking['booking_date'])) . '</p>';
        
        // Cancellation link with token
        $cancel_url = 'cancel_booking.php?token=' . urlencode($booking['cancellation_token']);
        echo '<p><strong>Cancellation Link:</strong> <a href="' . $cancel_url . '" class="cancel-link" onclick="return confirm(\'Are you sure you want to cancel this booking?\')">Cancel Booking</a></p>';
        
        echo '</div>';
    }
    echo '</div>';
} else {
    // No bookings found
    echo '<div class="no-results"><p>No bookings found for ' . htmlspecialchars($email) . '</p></div>';
}

$conn->close();
?>

<style>
/* Results display styling */
.bookings-results { max-width: 600px; margin: 1rem auto; }
.bookings-results h4 { color: #333; margin-bottom: 1rem; text-align: center; }
.booking-result-card { background: white; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.booking-result-card h5 { color: #495057; margin-bottom: 0.5rem; }
.booking-result-card p { margin-bottom: 0.5rem; color: #6c757d; font-size: 0.9rem; }

/* Status styling */
.status-booked { color: #28a745; font-weight: bold; }
.status-waitlisted { color: #ffc107; font-weight: bold; }

/* Link styling */
.cancel-link { color: #dc3545; text-decoration: none; font-weight: bold; }
.cancel-link:hover { text-decoration: underline; }

/* Empty state */
.no-results { text-align: center; padding: 2rem; color: #6c757d; }
.error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; text-align: center; }
</style>
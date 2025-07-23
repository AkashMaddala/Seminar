<!-- my_bookings.php -->
<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error">Please login to view your bookings.</div>';
    exit();
}

include_once 'db.php';
$conn = get_db_connection();
$user_id = $_SESSION['user_id'];
 
// Get user's active bookings
$query = "
    SELECT b.id, b.status, b.time_slot, b.contact_email, b.booking_date,
           s.title, s.description, s.date as seminar_date, s.time as seminar_time
    FROM bookings b
    JOIN seminars s ON b.seminar_id = s.id
    WHERE b.user_id = ? AND b.status != 'canceled'
    ORDER BY s.date ASC, b.time_slot ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="bookings-container">';
    echo '<h4>Your Current Bookings</h4>';
    
    // Display each booking
    while ($booking = $result->fetch_assoc()) {
        echo '<div class="booking-card">';
        echo '<h5>' . htmlspecialchars($booking['title']) . '</h5>';
        echo '<p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($booking['seminar_date'])) . '</p>';
        
        // Show the actual booked time slot
        if ($booking['time_slot']) {
            $end_time = date('g:i A', strtotime($booking['time_slot'] . ' +1 hour'));
            echo '<p><strong>Time Slot:</strong> ' . date('g:i A', strtotime($booking['time_slot'])) . ' - ' . $end_time . '</p>';
        } else {
            echo '<p><strong>Time:</strong> ' . date('g:i A', strtotime($booking['seminar_time'])) . '</p>';
        }
        
        // Contact and status info
        echo '<p><strong>Contact Email:</strong> ' . htmlspecialchars($booking['contact_email']) . '</p>';
        echo '<p><strong>Status:</strong> <span class="status-' . $booking['status'] . '">' . ucfirst($booking['status']) . '</span></p>';
        echo '<p><strong>Booked on:</strong> ' . date('M j, Y g:i A', strtotime($booking['booking_date'])) . '</p>';
        
        // Cancel button
        echo '<button onclick="cancelBooking(' . $booking['id'] . ')" class="btn btn-cancel btn-sm">Cancel Booking</button>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<div class="no-bookings"><p>You have no current bookings.</p></div>';
}

$conn->close();
?>

<style>
/* Container styling */
.bookings-container { max-width: 600px; margin: 1rem auto; }
.bookings-container h4 { color: #333; margin-bottom: 1rem; text-align: center; }

/* Booking cards */
.booking-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; }
.booking-card h5 { color: #495057; margin-bottom: 0.5rem; }
.booking-card p { margin-bottom: 0.5rem; color: #6c757d; font-size: 0.9rem; }

/* Status badges */
.status-booked { color: #28a745; font-weight: bold; }
.status-waitlisted { color: #ffc107; font-weight: bold; }

/* Buttons */
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
.btn { border: none; border-radius: 4px; cursor: pointer; }
.btn-cancel { background: #dc3545; color: white; }
.btn-cancel:hover { background: #c82333; }
.btn-cancel:disabled { background: #6c757d; cursor: not-allowed; }

/* Empty state */
.no-bookings { text-align: center; padding: 2rem; color: #6c757d; }
.error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; text-align: center; }
</style>

<script>
// Cancel booking with confirmation
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // Show loading state
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Cancelling...';
        button.disabled = true;
        
        // Redirect to cancel action
        window.location.href = 'actions.php?action=cancel&id=' + bookingId;
    }
}
</script>
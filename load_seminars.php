<!-- FILE: load_seminars.php -->
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error">Please login to view seminars.</div>';
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

// Get current user ID
$user_id = $_SESSION['user_id'];

// Query to get all available seminars with booking counts
$query = "
    SELECT 
        s.id,
        s.title,
        s.description,
        s.date,
        s.time,
        s.capacity,
        s.created_at,
        (SELECT COUNT(*) FROM bookings b WHERE b.seminar_id = s.id AND b.status = 'booked') as booked_count,
        (SELECT COUNT(*) FROM bookings b WHERE b.seminar_id = s.id AND b.status = 'waitlisted') as waitlist_count,
        (SELECT COUNT(*) FROM bookings b WHERE b.seminar_id = s.id AND b.user_id = ? AND b.status != 'canceled') as user_booked
    FROM seminars s
    WHERE s.date >= CURDATE()
    ORDER BY s.date ASC, s.time ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="seminars-grid">';
    
    while ($seminar = $result->fetch_assoc()) {
        $is_full = $seminar['booked_count'] >= $seminar['capacity'];
        $user_already_booked = $seminar['user_booked'] > 0;
        $spots_left = $seminar['capacity'] - $seminar['booked_count'];
        
        // Format date and time for display
        $formatted_date = date('l, F j, Y', strtotime($seminar['date']));
        $formatted_time = date('g:i A', strtotime($seminar['time']));
        $end_time = date('g:i A', strtotime($seminar['time'] . ' +1 hour'));
        
        echo '<div class="seminar-card">';
        echo '<h3>' . htmlspecialchars($seminar['title']) . '</h3>';
        echo '<p class="description">' . htmlspecialchars($seminar['description']) . '</p>';
        echo '<div class="seminar-details">';
        echo '<p><strong>Date:</strong> ' . $formatted_date . '</p>';
        echo '<p><strong>Time:</strong> ' . $formatted_time . ' - ' . $end_time . '</p>';
        echo '<p><strong>Duration:</strong> 1 hour</p>';
        
        if ($is_full) {
            echo '<p class="status full"><strong>Status:</strong> FULL (Waitlist available)</p>';
            echo '<p><strong>Waitlist:</strong> ' . $seminar['waitlist_count'] . ' people waiting</p>';
        } else {
            echo '<p class="status available"><strong>Status:</strong> ' . $spots_left . ' spots available</p>';
        }
        
        echo '</div>';
        
        // Action button
        if ($user_already_booked) {
            echo '<button class="btn btn-disabled" disabled>Already Registered</button>';
        } else {
            // Pass formatted date and time to the modal
            $onclick = "openBookingModal(" . $seminar['id'] . ", '" . 
                       htmlspecialchars($seminar['title'], ENT_QUOTES) . "', '" . 
                       $formatted_date . "', '" . 
                       $formatted_time . " - " . $end_time . "')";
            echo '<button onclick="' . $onclick . '" class="btn btn-primary">Book Now</button>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
} else {
    echo '<div class="no-seminars">';
    echo '<p>No upcoming seminars available at this time.</p>';
    echo '<p>Please check back later for new seminars!</p>';
    echo '</div>';
}

$conn->close();
?>

<style>
/* Seminars grid layout */
.seminars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

/* Seminar card styling */
.seminar-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.seminar-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.seminar-card h3 {
    color: #333;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.seminar-card .description {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.95rem;
    line-height: 1.5;
}

/* Seminar details */
.seminar-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.seminar-details p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    color: #555;
}

/* Status indicators */
.status {
    font-weight: bold;
    padding: 0.25rem 0;
}

.status.available {
    color: #28a745;
}

.status.full {
    color: #dc3545;
}

/* Button styling */
.btn-disabled {
    background: #6c757d !important;
    cursor: not-allowed !important;
    opacity: 0.65;
}

/* No seminars message */
.no-seminars {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-seminars p {
    margin: 0.5rem 0;
    font-size: 1.1rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .seminars-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<!-- get_time_slots.php -->
<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error">Please login to view time slots.</div>';
    exit();
}

// Validate inputs
if (!isset($_GET['date']) || !isset($_GET['seminar_id'])) {
    echo '<div class="error">Invalid request.</div>';
    exit();
}

$date = $_GET['date'];
$seminar_id = intval($_GET['seminar_id']);
$user_id = $_SESSION['user_id'];

// Validate date is not in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo '<div class="error">Cannot book for past dates.</div>';
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

// Get the seminar's original time
$seminar_stmt = $conn->prepare("SELECT time FROM seminars WHERE id = ?");
$seminar_stmt->bind_param("i", $seminar_id);
$seminar_stmt->execute();
$seminar_result = $seminar_stmt->get_result();
$seminar_data = $seminar_result->fetch_assoc();

if (!$seminar_data) {
    echo '<div class="error">Seminar not found.</div>';
    exit();
}

$seminar_time = $seminar_data['time'];

// Get all bookings for this user on the selected date
$booked_times_query = "
    SELECT b.time_slot
    FROM bookings b
    JOIN seminars s ON b.seminar_id = s.id
    WHERE b.user_id = ? 
    AND s.date = ?
    AND b.status != 'canceled'
";

$stmt = $conn->prepare($booked_times_query);
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_times = [];
while ($row = $result->fetch_assoc()) {
    if ($row['time_slot']) {
        $booked_times[] = $row['time_slot'];
    }
}

// For admin-created seminars (time after 12 PM), show only that specific time slot
$hour = intval(substr($seminar_time, 0, 2));
if ($hour >= 12) {
    // This is an admin-created seminar, show only its time slot
    $is_today = ($date == date('Y-m-d'));
    $current_time = date('H:i:s');
    
    $is_booked = in_array($seminar_time, $booked_times);
    $is_past = ($is_today && $seminar_time < $current_time);
    
    if (!$is_booked && !$is_past) {
        $end_time = date('g:i A', strtotime($seminar_time . ' +1 hour'));
        echo '<label class="time-slot-option">';
        echo '<input type="radio" name="time_slot" value="' . $seminar_time . '" required>';
        echo date('g:i A', strtotime($seminar_time)) . ' - ' . $end_time;
        echo '</label>';
    } elseif ($is_booked) {
        echo '<label class="time-slot-option disabled">';
        echo '<input type="radio" name="time_slot" value="' . $seminar_time . '" disabled>';
        echo date('g:i A', strtotime($seminar_time)) . ' - ' . date('g:i A', strtotime($seminar_time . ' +1 hour'));
        echo ' (You have another booking)';
        echo '</label>';
    } else {
        echo '<p class="info-text">This time slot has passed.</p>';
    }
} else {
    // For regular seminars (morning slots), show the usual 2-3 available slots
    $time_slots = [
        '08:00:00' => '8:00 AM - 9:00 AM',
        '09:00:00' => '9:00 AM - 10:00 AM',
        '10:00:00' => '10:00 AM - 11:00 AM',
        '11:00:00' => '11:00 AM - 12:00 PM'
    ];

    $is_today = ($date == date('Y-m-d'));
    $current_time = date('H:i:s');

    $available_count = 0;
    $slot_html = '';

    foreach ($time_slots as $time_value => $time_label) {
        $is_booked = in_array($time_value, $booked_times);
        $is_past = ($is_today && $time_value < $current_time);
        
        if (!$is_booked && !$is_past && $available_count < 3) {
            // Show only 2-3 available slots
            $slot_html .= '<label class="time-slot-option">';
            $slot_html .= '<input type="radio" name="time_slot" value="' . $time_value . '" required>';
            $slot_html .= $time_label;
            $slot_html .= '</label>';
            $available_count++;
        } elseif ($is_booked) {
            // Show as disabled if user has another booking
            $slot_html .= '<label class="time-slot-option disabled">';
            $slot_html .= '<input type="radio" name="time_slot" value="' . $time_value . '" disabled>';
            $slot_html .= $time_label . ' (You have another booking)';
            $slot_html .= '</label>';
        }
    }

    if ($available_count == 0) {
        echo '<p class="info-text">No available time slots for this date. You may have conflicts with other bookings or all slots are past.</p>';
    } else {
        echo $slot_html;
    }
}

$conn->close();
?>
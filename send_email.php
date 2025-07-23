<!-- send_email.php -->
<?php
// Email notification system using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function send_email($user_id, $seminar_id, $action, $contact_email = null, $cancellation_token = null) {
    include_once 'db.php';
    $conn = get_db_connection();

    // Get user and seminar details
    $stmt = $conn->prepare("
        SELECT u.email as user_email, u.name, s.title, s.date, s.time 
        FROM users u, seminars s 
        WHERE u.id = ? AND s.id = ?
    ");
    $stmt->bind_param("ii", $user_id, $seminar_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if (!$data) return false;

    // Email recipient (use contact email if provided)
    $to = $contact_email ?: $data['user_email'];
    $name = $data['name'];
    $title = $data['title'];
    $date = $data['date'];
    $time = $data['time'];
    
    // Get the actual booked time slot and cancellation token
    if (!$cancellation_token && in_array($action, ['booked', 'waitlisted', 'promoted'])) {
        $token_stmt = $conn->prepare("
            SELECT cancellation_token, time_slot 
            FROM bookings 
            WHERE user_id = ? 
            AND seminar_id = ? 
            AND status != 'canceled' 
            ORDER BY booking_date DESC 
            LIMIT 1
        ");
        $token_stmt->bind_param("ii", $user_id, $seminar_id);
        $token_stmt->execute();
        $token_result = $token_stmt->get_result()->fetch_assoc();
        $cancellation_token = $token_result['cancellation_token'] ?? null;
        $booked_time_slot = $token_result['time_slot'] ?? $time;
    } else {
        // For canceled bookings, get the time slot
        $time_stmt = $conn->prepare("
            SELECT time_slot 
            FROM bookings 
            WHERE user_id = ? 
            AND seminar_id = ? 
            ORDER BY booking_date DESC 
            LIMIT 1
        ");
        $time_stmt->bind_param("ii", $user_id, $seminar_id);
        $time_stmt->execute();
        $time_result = $time_stmt->get_result()->fetch_assoc();
        $booked_time_slot = $time_result['time_slot'] ?? $time;
    }
    
    // Format the time slot for display
    $display_time = date('g:i A', strtotime($booked_time_slot));
    $end_time = date('g:i A', strtotime($booked_time_slot . ' +1 hour'));
    $time_range = $display_time . ' - ' . $end_time;
    
    // Generate cancellation URL
    $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $cancel_url = $cancellation_token ? $base_url . '/cancel_booking.php?token=' . urlencode($cancellation_token) : '';
    
    // Build email content based on action type
    switch ($action) {
        case 'booked':
            $subject = "Seminar Booking Confirmed - {$title}";
            $message = "Dear {$name},\n\n";
            $message .= "Great news! Your booking for '{$title}' has been CONFIRMED!\n\n";
            $message .= "SEMINAR DETAILS:\n";
            $message .= "• Title: {$title}\n";
            $message .= "• Date: " . date('l, F j, Y', strtotime($date)) . "\n";
            $message .= "• Time Slot: {$time_range}\n";
            $message .= "• Status: CONFIRMED\n\n";
            if ($cancel_url) {
                $message .= "CANCELLATION:\n";
                $message .= "If you need to cancel, click here: {$cancel_url}\n\n";
            }
            $message .= "We look forward to seeing you there!\n\n";
            $message .= "Best regards,\nSeminar Booking System";
            break;
            
        case 'waitlisted':
            $subject = "Waitlist Confirmation - {$title}";
            $message = "Dear {$name},\n\n";
            $message .= "Thank you for your interest in '{$title}'!\n\n";
            $message .= "SEMINAR DETAILS:\n";
            $message .= "• Title: {$title}\n";
            $message .= "• Date: " . date('l, F j, Y', strtotime($date)) . "\n";
            $message .= "• Time Slot: {$time_range}\n";
            $message .= "• Status: WAITLISTED\n\n";
            $message .= "This seminar is currently full, but you've been added to our priority waitlist.\n\n";
            if ($cancel_url) {
                $message .= "LEAVE WAITLIST:\n";
                $message .= "If you want to leave the waitlist, click here: {$cancel_url}\n\n";
            }
            $message .= "Thank you for your patience!\n\n";
            $message .= "Best regards,\nSeminar Booking System";
            break;
            
        case 'promoted':
            $subject = "GREAT NEWS! You're In - {$title}";
            $message = "Dear {$name},\n\n";
            $message .= "FANTASTIC NEWS! A spot has opened up!\n\n";
            $message .= "You've been automatically moved from the waitlist to CONFIRMED for '{$title}'!\n\n";
            $message .= "SEMINAR DETAILS:\n";
            $message .= "• Title: {$title}\n";
            $message .= "• Date: " . date('l, F j, Y', strtotime($date)) . "\n";
            $message .= "• Time Slot: {$time_range}\n";
            $message .= "• Status: CONFIRMED\n\n";
            if ($cancel_url) {
                $message .= "CANCELLATION:\n";
                $message .= "If you need to cancel, click here: {$cancel_url}\n\n";
            }
            $message .= "We're excited to see you there!\n\n";
            $message .= "Best regards,\nSeminar Booking System";
            break;
            
        case 'canceled':
            $subject = "Booking Cancelled - {$title}";
            $message = "Dear {$name},\n\n";
            $message .= "Your booking for '{$title}' has been successfully cancelled.\n\n";
            $message .= "CANCELLED SEMINAR:\n";
            $message .= "• Title: {$title}\n";
            $message .= "• Date: " . date('l, F j, Y', strtotime($date)) . "\n";
            $message .= "• Time Slot: {$time_range}\n";
            $message .= "• Status: CANCELLED\n\n";
            $message .= "If you change your mind, you can book again if spots are available.\n\n";
            $message .= "We hope to see you at future seminars!\n\n";
            $message .= "Best regards,\nSeminar Booking System";
            break;
            
        default:
            return false;
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Gmail SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'akash.maddala.am@gmail.com';      
        $mail->Password   = 'vbqjikldmxaslvmx';  // App password (not regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email headers
        $mail->setFrom('your-email@gmail.com', 'Seminar Booking System');
        $mail->addAddress($to, $name);

        // Email content
        $mail->isHTML(false);  // Plain text email
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send email
        $mail->send();
        
        // Log success
        $log = "\n" . str_repeat("=", 50) . "\n";
        $log .= "EMAIL SENT: " . date('Y-m-d H:i:s') . "\n";
        $log .= "To: {$to}\n";
        $log .= "Subject: {$subject}\n";
        $log .= "Status: SUCCESS\n";
        $log .= str_repeat("=", 50) . "\n";
        file_put_contents('email_log.txt', $log, FILE_APPEND | LOCK_EX);
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        // Log failure
        $log = "\n" . str_repeat("=", 50) . "\n";
        $log .= "EMAIL FAILED: " . date('Y-m-d H:i:s') . "\n";
        $log .= "To: {$to}\n";
        $log .= "Subject: {$subject}\n";
        $log .= "Error: " . $mail->ErrorInfo . "\n";
        $log .= str_repeat("=", 50) . "\n";
        file_put_contents('email_log.txt', $log, FILE_APPEND | LOCK_EX);
        
        $conn->close();
        return false;
    }
}
?>
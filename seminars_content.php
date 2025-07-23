<!-- seminars_content.php -->
<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Guest user view -->
    <div class="login-prompt">
        <h2>Seminars</h2>
        <p>Please <a href="auth.php?action=login">login</a> to view and book available seminars.</p>
        <p>Don't have an account? <a href="auth.php?action=register">Register here</a></p>
    </div>
<?php else: ?>
    <!-- Logged in user view -->
    <div class="seminars-page">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <div id="seminars">
            <!-- AJAX loaded content from here -->
            <button onclick="loadSeminars()" class="btn btn-primary">Load Available Seminars</button>
        </div>
    </div>
    
    <!-- Booking modal popup (same as home page) -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Book Seminar</h2>
            <form id="bookingForm" method="post" action="actions.php">
                <input type="hidden" id="modalSeminarId" name="seminar_id">
                <input type="hidden" name="action" value="book">
                
                <!-- Seminar title display -->
                <div class="form-group">
                    <label>Seminar:</label>
                    <div class="seminar-display">
                        <h3 id="seminarTitle"></h3>
                    </div>
                </div>
                
                <!-- Contact email field -->
                <div class="form-group">
                    <label for="contactEmail">Your Email:</label>
                    <?php
                        include_once 'db.php';
                        $conn = get_db_connection();
                        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $user_email = $stmt->get_result()->fetch_assoc()['email'];
                    ?>
                    <input type="email" name="contact_email" id="contactEmail" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                </div>
                
                <!-- Date selection -->
                <div class="form-group">
                    <label for="bookingDate">Select Date:</label>
                    <input type="date" name="booking_date" id="bookingDate" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           required onchange="updateTimeSlots()">
                </div>
                
                <!-- Time slot selection -->
                <div class="form-group">
                    <label>Select Time Slot:</label>
                    <div id="timeSlotOptions">
                        <p class="info-text">Please select a date first</p>
                    </div>
                </div>
                
                <!-- Custom time slot option -->
                <div class="form-group custom-slot-section">
                    <label>
                        <input type="checkbox" id="customSlotToggle" onchange="toggleCustomSlot()">
                        Create custom time slot
                    </label>
                    <div id="customSlotFields" style="display: none; margin-top: 10px;">
                        <input type="time" name="custom_time" id="customTime" 
                               min="08:00" max="16:00">
                        <small>Select time between 8:00 AM and 4:00 PM</small>
                    </div>
                </div>
                
                <!-- Form action buttons -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
// Load seminars via AJAX
function loadSeminars() {
    const xhr = new XMLHttpRequest();
    xhr.onload = function() {
        document.getElementById("seminars").innerHTML = this.responseText;
    };
    xhr.onerror = function() {
        document.getElementById("seminars").innerHTML = '<div class="error">Error loading seminars.</div>';
    };
    xhr.open("GET", "load_seminars.php", true);
    xhr.send();
}

// Load user's personal bookings
function loadMyBookings() {
    const xhr = new XMLHttpRequest();
    xhr.onload = function() {
        document.getElementById("myBookings").innerHTML = this.responseText;
    };
    xhr.open("GET", "my_bookings.php", true);
    xhr.send();
}

// Check bookings by email for guests
function checkBookings(event) {
    event.preventDefault();
    const email = document.getElementById('checkEmail').value;
    if (!email) return;
    
    const xhr = new XMLHttpRequest();
    xhr.onload = function() {
        document.getElementById("bookingResults").innerHTML = this.responseText;
    };
    xhr.open("POST", "check_bookings.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("email=" + encodeURIComponent(email));
}

// Open booking modal with seminar details
function openBookingModal(seminarId, title) {
    document.getElementById('modalSeminarId').value = seminarId;
    document.getElementById('seminarTitle').textContent = title;
    document.getElementById('bookingModal').style.display = 'block';
    
    // Reset form
    document.getElementById('bookingDate').value = '';
    document.getElementById('timeSlotOptions').innerHTML = '<p class="info-text">Please select a date first</p>';
    document.getElementById('customSlotToggle').checked = false;
    document.getElementById('customSlotFields').style.display = 'none';
}

// Update available time slots based on selected date
function updateTimeSlots() {
    const date = document.getElementById('bookingDate').value;
    const seminarId = document.getElementById('modalSeminarId').value;
    
    if (!date) return;
    
    // Fetch available time slots via AJAX
    const xhr = new XMLHttpRequest();
    xhr.onload = function() {
        document.getElementById('timeSlotOptions').innerHTML = this.responseText;
    };
    xhr.open("GET", "get_time_slots.php?date=" + date + "&seminar_id=" + seminarId, true);
    xhr.send();
}

// Toggle custom time slot fields
function toggleCustomSlot() {
    const customFields = document.getElementById('customSlotFields');
    const regularSlots = document.querySelectorAll('input[name="time_slot"]');
    const customTimeInput = document.getElementById('customTime');
    
    if (document.getElementById('customSlotToggle').checked) {
        customFields.style.display = 'block';
        // Uncheck and disable regular time slots
        regularSlots.forEach(slot => {
            slot.checked = false;
            slot.required = false;
        });
        customTimeInput.required = true;
    } else {
        customFields.style.display = 'none';
        customTimeInput.value = '';
        customTimeInput.required = false;
        // Re-enable regular slots
        regularSlots.forEach(slot => {
            slot.required = true;
        });
    }
}

// Form validation before submit
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const customToggle = document.getElementById('customSlotToggle');
            const customTime = document.getElementById('customTime');
            const regularSlots = document.querySelectorAll('input[name="time_slot"]:checked');
            
            if (customToggle.checked) {
                // Custom slot mode - check if custom time is filled
                if (!customTime.value) {
                    e.preventDefault();
                    alert('Please select a custom time between 8:00 AM and 4:00 PM');
                    return false;
                }
            } else {
                // Regular slot mode - check if a slot is selected
                if (regularSlots.length === 0) {
                    e.preventDefault();
                    alert('Please select a time slot');
                    return false;
                }
            }
        });
    }
});

// Close booking modal
function closeModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

// Cancel booking with confirmation
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Cancelling...';
        button.disabled = true;
        
        window.location.href = 'actions.php?action=cancel&id=' + bookingId;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
/* Guest prompt styling */
.login-prompt { text-align: center; padding: 3rem; max-width: 600px; margin: 0 auto; }
.login-prompt h2 { color: #333; margin-bottom: 1rem; }
.login-prompt p { font-size: 1rem; color: #666; margin-bottom: 1rem; }
.login-prompt a { color: #007cba; text-decoration: none; font-weight: bold; }
.login-prompt a:hover { text-decoration: underline; }

/* Logged in page styling */
.seminars-page { max-width: 800px; margin: 0 auto; padding: 1rem; }
.seminars-page h2 { text-align: center; color: #333; margin-bottom: 2rem; }
#seminars { text-align: center; }

/* Button styling */
.btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; }
.btn-primary { background: #007cba; color: white; }
.btn-primary:hover { background: #005a87; }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #545b62; }

/* Modal popup styling */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto; }
.modal-content { background-color: white; margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; position: relative; max-height: 80vh; overflow-y: auto; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; right: 1rem; top: 1rem; cursor: pointer; }
.close:hover { color: black; }

/* Form styling */
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
.form-group input[type="email"], .form-group input[type="date"], .form-group input[type="time"] {
    width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box;
}
.form-group input[readonly] { background-color: #f8f9fa; color: #6c757d; }
.form-group small { display: block; margin-top: 0.25rem; color: #666; font-size: 0.875rem; }

/* Seminar display */
.seminar-display { background: #f8f9fa; padding: 1rem; border-radius: 4px; }
.seminar-display h3 { margin: 0; color: #007cba; }

/* Time slot styling */
.time-slot-option { 
    display: block; 
    padding: 0.75rem; 
    margin: 0.5rem 0; 
    border: 1px solid #ddd; 
    border-radius: 4px; 
    cursor: pointer;
    transition: all 0.2s;
}
.time-slot-option:hover { background: #f8f9fa; border-color: #007cba; }
.time-slot-option input[type="radio"] { margin-right: 0.5rem; }
.time-slot-option.disabled { opacity: 0.6; cursor: not-allowed; background: #f5f5f5; }
.info-text { color: #666; text-align: center; padding: 1rem; }

/* Custom slot section */
.custom-slot-section { background: #f8f9fa; padding: 1rem; border-radius: 4px; }
.custom-slot-section label { cursor: pointer; }
</style>
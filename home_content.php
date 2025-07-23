<!-- FILE: home_content.php -->
<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Guest user homepage content-->
    <div class="welcome-section">
        <h1>Welcome to Seminar Booking System</h1>
        <p>Discover and book amazing seminars to enhance your knowledge and skills.</p>
        
        <!-- Email booking check for guests -->
        <div class="booking-check-section">
            <h3>Check Your Bookings</h3>
            <p>Enter your email to see your current bookings:</p>
            <form id="checkBookingsForm" onsubmit="checkBookings(event)">
                <div class="input-group">
                    <input type="email" id="checkEmail" placeholder="Enter your email address" required>
                    <button type="submit" class="btn btn-secondary">Check Bookings</button>
                </div>
            </form>
            <div id="bookingResults"></div>
        </div>
        
        <!-- Authentication links -->
        <div class="auth-buttons">
            <a href="auth.php?action=login" class="btn btn-primary">Login</a>
            <a href="auth.php?action=register" class="btn btn-secondary">Register</a>
        </div>
    </div>
<?php else: ?>
    <!--Logged in user dashboard -->
    <div class="dashboard">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p>Ready to explore our latest seminars?</p>
        
        <!-- User's booking management -->
        <div class="user-bookings-section">
            <h3>Your Current Bookings</h3>
            <button onclick="loadMyBookings()" class="btn btn-info">View My Bookings</button>
            <div id="myBookings"></div>
        </div>
        
        <!-- Seminar loading section -->
        <div id="seminars">
            <button onclick="loadSeminars()" class="btn btn-primary">Load Available Seminars</button>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <button onclick="openCreateSeminarModal()" class="btn btn-success" style="margin-left: 10px;">Create Seminar</button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <!-- Create Seminar Modal for Admin -->
    <div id="createSeminarModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h2>Create New Seminar</h2>
            <form id="createSeminarForm" method="post" action="create_seminar.php">
                <div class="form-group">
                    <label for="seminarTitle">Seminar Title:</label>
                    <input type="text" id="seminarTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="seminarDescription">Description:</label>
                    <textarea id="seminarDescription" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="seminarDate">Date:</label>
                    <input type="date" id="seminarDate" name="date" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           onchange="checkAvailableTimes()" required>
                </div>
                
                <div class="form-group">
                    <label for="seminarTime">Time (seminars are 1 hour long):</label>
                    <input type="time" id="seminarTime" name="time" 
                           onchange="checkTimeConflict()" required>
                    <small>Select any time</small>
                    <div id="timeConflictMessage" style="color: red; margin-top: 5px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="seminarCapacity">Capacity (2-5):</label>
                    <input type="number" id="seminarCapacity" name="capacity" 
                           min="2" max="5" value="3" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success" id="createSeminarBtn">Create Seminar</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <!-- Success message display -->
    <div class="message success">
        <?php 
        switch($_GET['success']) {
            case 'booked': echo "Seminar booked successfully! Confirmation email sent with cancellation link."; break;
            case 'canceled': echo "Booking canceled successfully. Confirmation email sent."; break;
            case 'waitlisted': echo "Added to waitlist. We'll notify you if a spot opens up."; break;
            case 'seminar_created': echo "Seminar created successfully!"; break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <!-- Error message display -->
    <div class="message error">
        <?php 
        switch($_GET['error']) {
            case 'already_booked': echo "You have already booked this seminar or are on the waitlist."; break;
            case 'invalid_booking': echo "Invalid booking request."; break;
            case 'seminar_not_found': echo "Seminar not found."; break;
            case 'invalid_token': echo "Invalid cancellation link."; break;
            case 'time_conflict': echo "You already have a booking at this time. Please select a different seminar."; break;
            case 'missing_data': echo "Please fill in all required fields."; break;
            case 'seminar_time_conflict': 
                $conflict = isset($_GET['conflict']) ? htmlspecialchars($_GET['conflict']) : 'another seminar';
                echo "Cannot create seminar: Time conflicts with '{$conflict}' on this date."; 
                break;
            case 'past_date': echo "Cannot create seminars for past dates."; break;
        }
        ?>
    </div>
<?php endif; ?>

<!-- Simplified Booking modal popup -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Confirm Booking</h2>
        <form id="bookingForm" method="post" action="actions.php">
            <input type="hidden" id="modalSeminarId" name="seminar_id">
            <input type="hidden" name="action" value="book">
            
            <!-- Seminar details display -->
            <div class="form-group">
                <div class="seminar-confirm-display">
                    <h3 id="seminarTitle"></h3>
                    <div class="detail-row">
                        <span class="label">Date:</span>
                        <span id="seminarDate"></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Time:</span>
                        <span id="seminarTime"></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Duration:</span>
                        <span>1 hour</span>
                    </div>
                </div>
            </div>
            
            <!-- Contact email field -->
            <div class="form-group">
                <label for="contactEmail">Confirmation Email:</label>
                <?php
                    $conn = get_db_connection();
                    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $user_email = $stmt->get_result()->fetch_assoc()['email'];
                ?>
                <input type="email" name="contact_email" id="contactEmail" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                <small>Booking confirmation will be sent to this email</small>
            </div>
            
            <!-- Form action buttons -->
            <div class="form-group button-group">
                <button type="submit" class="btn btn-primary">Confirm Booking</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Global variable to store existing seminars
let existingSeminars = [];

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

// Open booking modal with seminar details (simplified)
function openBookingModal(seminarId, title, date, time) {
    document.getElementById('modalSeminarId').value = seminarId;
    document.getElementById('seminarTitle').textContent = title;
    document.getElementById('seminarDate').textContent = date;
    document.getElementById('seminarTime').textContent = time;
    document.getElementById('bookingModal').style.display = 'block';
}

// Open create seminar modal
function openCreateSeminarModal() {
    document.getElementById('createSeminarModal').style.display = 'block';
}

// Close create seminar modal
function closeCreateModal() {
    document.getElementById('createSeminarModal').style.display = 'none';
    // Reset form
    document.getElementById('createSeminarForm').reset();
    document.getElementById('timeConflictMessage').textContent = '';
}

// Check available times when date changes
function checkAvailableTimes() {
    const date = document.getElementById('seminarDate').value;
    
    if (!date) {
        existingSeminars = [];
        return;
    }
    
    // Fetch existing seminars for this date
    const xhr = new XMLHttpRequest();
    xhr.onload = function() {
        const response = JSON.parse(this.responseText);
        if (response.seminars) {
            existingSeminars = response.seminars;
            checkTimeConflict(); // Check if current time conflicts
        }
    };
    xhr.open("GET", "check_seminar_times.php?date=" + date, true);
    xhr.send();
}

// Check if selected time conflicts with existing seminars
function checkTimeConflict() {
    const selectedTime = document.getElementById('seminarTime').value;
    const conflictMessage = document.getElementById('timeConflictMessage');
    const createBtn = document.getElementById('createSeminarBtn');
    
    if (!selectedTime || existingSeminars.length === 0) {
        conflictMessage.textContent = '';
        createBtn.disabled = false;
        return;
    }

    // Convert selected time to minutes for easier comparison
    const [hours, minutes] = selectedTime.split(':').map(Number);
    const selectedMinutes = hours * 60 + minutes;
    const selectedEndMinutes = selectedMinutes + 60; // 1 hour duration
    
    // Check for conflicts
    for (const seminar of existingSeminars) {
        const [semHours, semMinutes] = seminar.time.split(':').slice(0, 2).map(Number);
        const seminarStartMinutes = semHours * 60 + semMinutes;
        const seminarEndMinutes = seminarStartMinutes + 60;
        
        // Check if time ranges overlap
        if ((selectedMinutes < seminarEndMinutes && selectedEndMinutes > seminarStartMinutes)) {
            conflictMessage.textContent = `Conflict: "${seminar.title}" is scheduled from ${formatTime(seminar.time)} to ${formatTime(addHour(seminar.time))}`;
            createBtn.disabled = true;
            return;
        }
    }
    
    conflictMessage.textContent = '';
    createBtn.disabled = false;
}

// Format time for display
function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':').slice(0, 2);
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const displayHour = h > 12 ? h - 12 : (h === 0 ? 12 : h);
    return `${displayHour}:${minutes} ${ampm}`;
}

// Add one hour to time string
function addHour(timeStr) {
    const [hours, minutes] = timeStr.split(':').slice(0, 2).map(Number);
    const newHours = hours + 1;
    return `${newHours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
}

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
    const createModal = document.getElementById('createSeminarModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
    if (event.target == createModal) {
        createModal.style.display = 'none';
    }
}
</script>

<style>
/* Welcome and dashboard sections */
.welcome-section, .dashboard { text-align: center; padding: 2rem; max-width: 800px; margin: 0 auto; }
.welcome-section h1, .dashboard h1 { color: #333; margin-bottom: 1rem; }
.welcome-section p, .dashboard p { font-size: 1.1rem; color: #666; margin-bottom: 2rem; }

/* Booking check section */
.booking-check-section, .user-bookings-section {
    background: white; border: 1px solid #ddd; border-radius: 8px; padding: 2rem; margin: 2rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.booking-check-section h3, .user-bookings-section h3 { color: #333; margin-bottom: 1rem; }

/* Input styling */
.input-group { display: flex; gap: 1rem; justify-content: center; margin: 1rem 0; flex-wrap: wrap; }
.input-group input[type="email"] { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; min-width: 250px; }

/* Button styling */
.auth-buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; }
.btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 1rem; }
.btn-primary { background: #007cba; color: white; }
.btn-primary:hover { background: #005a87; }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #545b62; }
.btn-info { background: #17a2b8; color: white; }
.btn-info:hover { background: #138496; }
.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; }
.btn:disabled { background: #6c757d; cursor: not-allowed; opacity: 0.65; }

/* Message styling */
.message { padding: 1rem; margin: 1rem auto; border-radius: 4px; text-align: center; max-width: 600px; }
.message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Modal popup styling */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto; }
.modal-content { background-color: white; margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; position: relative; max-height: 80vh; overflow-y: auto; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; right: 1rem; top: 1rem; cursor: pointer; }
.close:hover { color: black; }

/* Form styling */
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
.form-group input[type="email"], .form-group input[type="date"], .form-group input[type="time"], .form-group input[type="text"], .form-group input[type="number"] {
    width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box;
}
.form-group input[readonly] { background-color: #f8f9fa; color: #6c757d; }
.form-group small { display: block; margin-top: 0.25rem; color: #666; font-size: 0.875rem; }

/* Textarea styling */
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: Arial, sans-serif;
    resize: vertical;
}

/* Seminar confirm display */
.seminar-confirm-display { 
    background: #f8f9fa; 
    padding: 1.5rem; 
    border-radius: 6px; 
    border: 1px solid #e9ecef;
}
.seminar-confirm-display h3 { 
    margin: 0 0 1rem 0; 
    color: #007cba; 
    font-size: 1.5rem;
}
.detail-row { 
    display: flex; 
    justify-content: space-between; 
    padding: 0.5rem 0; 
    border-bottom: 1px solid #dee2e6;
}
.detail-row:last-child { border-bottom: none; }
.detail-row .label { 
    font-weight: bold; 
    color: #495057; 
}
.detail-row span:last-child { 
    color: #212529; 
}

/* Button group */
.button-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Result containers */
#bookingResults, #myBookings { margin-top: 1rem; }
.error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; text-align: center; margin: 1rem 0; }

/* Time conflict message */
#timeConflictMessage { font-size: 0.875rem; margin-top: 0.5rem; }
</style>



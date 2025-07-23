<!-- admin_dashboard.php -->
<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

include_once 'db.php';
$conn = get_db_connection();

// Handle seminar creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_seminar') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'] . ':00'; // Add seconds
    $capacity = intval($_POST['capacity']);
    
    // Validate inputs
    if (empty($title) || empty($date) || empty($time) || $capacity < 2 || $capacity > 5) {
        $error = "Please fill all fields correctly. Capacity must be between 2 and 5.";
    } else {
        // Check for time conflicts (seminars are 1 hour long)
        $end_time = date('H:i:s', strtotime($time . ' +1 hour'));
        
        $conflict_stmt = $conn->prepare("
            SELECT title, time 
            FROM seminars 
            WHERE date = ? 
            AND (
                (time >= ? AND time < ?) OR
                (? >= time AND ? < DATE_ADD(time, INTERVAL 1 HOUR))
            )
        ");
        $conflict_stmt->bind_param("sssss", $date, $time, $end_time, $time, $time);
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result();
        
        if ($conflict_result->num_rows > 0) {
            $conflict = $conflict_result->fetch_assoc();
            $conflict_time = date('g:i A', strtotime($conflict['time']));
            $conflict_end = date('g:i A', strtotime($conflict['time'] . ' +1 hour'));
            $error = "Time conflict: '{$conflict['title']}' is scheduled from {$conflict_time} to {$conflict_end} on this date.";
        } else {
            // Create seminar
            $stmt = $conn->prepare("INSERT INTO seminars (title, description, date, time, capacity) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $description, $date, $time, $capacity);
            
            if ($stmt->execute()) {
                $success = "Seminar created successfully!";
            } else {
                $error = "Failed to create seminar.";
            }
        }
    }
}

// Get all seminars
$seminars_query = "SELECT * FROM seminars ORDER BY date DESC, time DESC";
$seminars_result = $conn->query($seminars_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Seminar Booking System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        
        /* Header */
        .admin-header { background: #333; color: white; padding: 1rem; }
        .admin-header h1 { display: inline-block; }
        .logout-btn { float: right; background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout-btn:hover { background: #c82333; }
        
        /* Container */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        /* Create seminar form */
        .create-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .create-section h2 { margin-bottom: 1.5rem; color: #333; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        
        /* Messages */
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Seminars list */
        .seminars-section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .seminars-section h2 { margin-bottom: 1.5rem; color: #333; }
        
        .seminars-table { width: 100%; border-collapse: collapse; }
        .seminars-table th, .seminars-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        .seminars-table th { background: #f8f9fa; font-weight: bold; color: #333; }
        .seminars-table tr:hover { background: #f8f9fa; }
        
        /* Buttons */
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        
        /* No seminars message */
        .no-seminars { text-align: center; padding: 2rem; color: #666; }
    </style>
    <script>
    function checkTimeConflicts() {
        const date = document.getElementById('date').value;
        const time = document.getElementById('time').value;
        
        if (!date || !time) return;
        
        // Check for conflicts via AJAX
        const xhr = new XMLHttpRequest();
        xhr.onload = function() {
            const response = JSON.parse(this.responseText);
            const conflictMsg = document.getElementById('conflict-message');
            const submitBtn = document.getElementById('submit-btn');
            
            if (response.conflict) {
                conflictMsg.style.display = 'block';
                conflictMsg.textContent = response.message;
                submitBtn.disabled = true;
            } else {
                conflictMsg.style.display = 'none';
                submitBtn.disabled = false;
            }
        };
        xhr.open("GET", "check_admin_conflicts.php?date=" + date + "&time=" + time, true);
        xhr.send();
    }
    </script>
</head>
<body>
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <a href="actions.php?action=logout" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <!-- Create new seminar section -->
        <div class="create-section">
            <h2>Create New Seminar</h2>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_seminar">
                
                <div class="form-group">
                    <label for="title">Seminar Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" 
                               onchange="checkTimeConflicts()" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Time (seminars are 1 hour long):</label>
                        <input type="time" id="time" name="time" 
                               onchange="checkTimeConflicts()" required>
                        <small style="color: #666;">Select any time (24-hour format)</small>
                        <div id="conflict-message" style="color: red; margin-top: 5px; display: none;"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="capacity">Capacity (2-5):</label>
                    <input type="number" id="capacity" name="capacity" min="2" max="5" value="3" required>
                </div>
                
                <button type="submit" id="submit-btn" class="btn btn-primary">Create Seminar</button>
            </form>
        </div>
        
        <!-- Existing seminars section -->
        <div class="seminars-section">
            <h2>All Seminars</h2>
            
            <?php if ($seminars_result->num_rows > 0): ?>
                <table class="seminars-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Capacity</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($seminar = $seminars_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $seminar['id']; ?></td>
                                <td><?php echo htmlspecialchars($seminar['title']); ?></td>
                                <td><?php echo htmlspecialchars($seminar['description']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($seminar['date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($seminar['time'])); ?></td>
                                <td>1 hour</td>
                                <td><?php echo $seminar['capacity']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($seminar['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-seminars">No seminars created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
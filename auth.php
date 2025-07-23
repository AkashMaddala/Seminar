<!-- auth.php -->
<?php
// Handle user registration and login
include_once 'db.php';
session_start();

// Redirect logged-in users to homepage (except for registration)
if (isset($_SESSION['user_id']) && (!isset($_GET['action']) || $_GET['action'] !== 'register')) {
    header("Location: index.php");
    exit();
}

// Validate action parameter
if (!isset($_GET['action']) || !in_array($_GET['action'], ['register', 'login'])) {
    header("Location: auth.php?action=login");
    exit();
}

$message = $error = '';

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $message = "You have been successfully logged out.";
}

// Handle registration form
if ($_GET['action'] == 'register') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get and validate form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Basic validation
        if (empty($name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $conn = get_db_connection();
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "An account with this email already exists.";
            } else {
                // Create new user account
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $password_hash);
                
                if ($stmt->execute()) {
                    header("Location: auth.php?action=login&message=registered");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
    $title = "Register";
    $content = "register_form.php";
    
// Handle login form
} elseif ($_GET['action'] == 'login') {
    // Check for registration success message
    if (isset($_GET['message']) && $_GET['message'] === 'registered') {
        $message = "Registration successful! Please log in.";
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Check for admin hardcoded login
        if ($email === 'admin' && $password === 'asdfgh') {
            // Admin login with username instead of email
            $conn = get_db_connection();
            $stmt = $conn->prepare("SELECT id, name, is_admin FROM users WHERE email = 'admin@seminarbooking.com' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            
            if ($admin) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['is_admin'] = true;
                header("Location: admin_dashboard.php");
                exit();
            }
        } elseif (empty($email) || empty($password)) {
            $error = "Please enter both email/username and password.";
        } else {
            $conn = get_db_connection();
            
            // Verify user credentials
            $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful - create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
                
                if ($user['is_admin']) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email/username or password.";
            }
        }
    }
    $title = "Login";
    $content = "login_form.php";
}

// Include layout template
include 'layout.php';
?>
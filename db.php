<?php
// Database connection handler - prevents redeclaration errors
if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        // Static variable maintains single connection per request
        static $conn = null;
        
        if ($conn === null) {
            // Database configuration
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "seminar_booking";
            
            // Create MySQL connection
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            
            // Set UTF-8 encoding
            $conn->set_charset("utf8mb4");
        }
        
        return $conn;
    }
}
?>
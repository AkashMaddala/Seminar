<?php
// Main entry point - loads homepage
session_start();
include_once 'db.php'; 

// Set layout variables
$title = "Home - Seminar Booking System";
$content = "home_content.php";
include 'layout.php';
?>
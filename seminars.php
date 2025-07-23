<?php
// Seminars page entry point
session_start();
include_once 'db.php';

// Set page variables for layout
$title = "Seminars";
$content = "seminars_content.php";
include 'layout.php';
?>
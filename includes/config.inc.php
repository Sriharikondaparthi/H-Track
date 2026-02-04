<?php
// includes/config.inc.php

// start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// timezone
date_default_timezone_set('Asia/Kolkata');

// DB credentials
$db_host = '127.0.0.1';        
$db_user = 'root';             
$db_pass = '';                 
$db_name = 'hostel_management_system'; // << YOUR ACTUAL DB NAME

// create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// check connection
if ($conn->connect_errno) {
    die("Database connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

// charset
$conn->set_charset("utf8mb4");
?>

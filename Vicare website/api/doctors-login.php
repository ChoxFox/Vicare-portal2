<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce a pure JSON response data stream
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// FIXED INCLUSION ROADMAP: Points cleanly to your renamed database configuration module
include_once "api/database-config.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (Your login query lines continue flawlessly)

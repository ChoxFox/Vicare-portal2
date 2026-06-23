<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Completely wipe out all active global session tracking values from browser memory
$_SESSION = array();

// 2. Explicitly clear out local browser session cookie data keys securely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the parent server session token instance entirely
session_destroy();

// REDIRECT TARGET: Instantly throws the logged-out professional right back to your home landing page
header("Location: index.html");
exit();
?>

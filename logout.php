<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page (adjust path based on your directory structure)
header("Location: login/login.html");
exit();
?> 
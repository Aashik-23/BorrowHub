<?php
require_once __DIR__ . '/../includes/functions.php';

// Clear all session data
$_SESSION = [];

// Remove the session cookie itself
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session data on the server
session_destroy();

// Start a fresh session just to hold the flash message on the next page
session_start();
set_flash('success', "You've been logged out.");

redirect('login.php');

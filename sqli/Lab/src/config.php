<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('DB_USER') ?: 'blog_user');
define('DB_PASS', getenv('DB_PASS') ?: 'blog_pass');
define('DB_NAME', getenv('DB_NAME') ?: 'blog_db');

// Connect to database - INTENTIONALLY VULNERABLE (no error handling)
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Start session
session_start();
?>

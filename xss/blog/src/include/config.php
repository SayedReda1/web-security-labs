<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('MYSQL_USER') ?: 'blog_user');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: 'blog_pass');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'blog_db');

// Connect to database
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

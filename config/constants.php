<?php
// // Start session only if not already started
// if (session_status() !== PHP_SESSION_ACTIVE) {
//     session_start();
// }

// if (!defined('SITEURL')) {
//     define('SITEURL', 'http://localhost/foodweb/');
// }         

$host = "localhost:3306";
$username = "root";
$pass = "";
$db = "myshop";

// Only create connection if it doesn't exist
if (!isset($conn)) {
    $conn = mysqli_connect($host, $username, $pass, $db);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}
?>
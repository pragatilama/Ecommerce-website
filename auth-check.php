<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function getCustomerId() {
    return $_SESSION['customer_id'] ?? null;
}

function redirectToLogin() {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
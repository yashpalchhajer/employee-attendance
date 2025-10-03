<?php
$secretsPath = __DIR__ . '/env.php'; 
if (!file_exists($secretsPath)) {
    die('Configuration error: missing enb.php file.');
}
require_once $secretsPath;

$required = [
    'DB_HOST','DB_USERNAME','DB_PASSWORD','DB_NAME',
    'MAIL_HOST','MAIL_USERNAME','MAIL_PASSWORD','MAIL_PORT','MAIL_FROM','MAIL_FROM_NAME','BASE_URL'
];
foreach ($required as $c) {
    if (!defined($c)) {
        die("Configuration error: missing constant {$c} in enb.php.");
    }
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}
?>

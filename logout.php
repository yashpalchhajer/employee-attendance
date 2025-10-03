<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("UPDATE attendance SET check_out = NOW() WHERE user_id = ? AND date = ? AND check_out IS NULL");
    $stmt->execute([$user_id, $today]);
}

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>

<?php
require 'config.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = intval($_GET['id']);
$newPassword = password_hash("reset123", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset = 1 WHERE id = ?");
$stmt->execute([$newPassword, $id]);

header("Location: users.php?reset=success");
exit;

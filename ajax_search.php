<?php
require 'config.php';

if (!isset($_GET['term']) || strlen($_GET['term']) < 2) {
    echo json_encode([]);
    exit;
}

$term = "%{$_GET['term']}%";

$stmt = $pdo->prepare("SELECT word FROM words WHERE word LIKE ? LIMIT 10");
$stmt->execute([$term]);

$results = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($results);

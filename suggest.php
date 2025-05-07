<?php
require 'config.php';

if (isset($_GET['q']) && strlen($_GET['q']) >= 2) {
    $term = $_GET['q'];
    $stmt = $pdo->prepare("SELECT word FROM words WHERE word LIKE ? LIMIT 10");
    $stmt->execute(["%$term%"]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($results);
}
?>

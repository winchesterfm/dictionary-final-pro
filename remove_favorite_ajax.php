<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['word_id'])) {
    $userId = $_SESSION['user']['id'];
    $wordId = intval($_POST['word_id']);

    // Vérifier l'existence
    $stmt = $pdo->prepare("SELECT w.word FROM favorites f JOIN words w ON f.word_id = w.id WHERE f.user_id = ? AND f.word_id = ?");
    $stmt->execute([$userId, $wordId]);
    $word = $stmt->fetchColumn();

    if ($word) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND word_id = ?")->execute([$userId, $wordId]);
        log_action($pdo, $userId, 'retrait_favori', "Retrait du mot \"$word\" des favoris");

        echo json_encode(['status' => 'success', 'message' => 'Favori retiré']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Échec de suppression']);

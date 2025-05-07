<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['word'])) {
    $word = trim($_POST['word']);
    $userId = $_SESSION['user']['id'];

    // Chercher l'ID du mot
    $stmt = $pdo->prepare("SELECT id FROM words WHERE word = ?");
    $stmt->execute([$word]);
    $wordData = $stmt->fetch();

    if ($wordData) {
        $wordId = $wordData['id'];

        // Vérifier si ce mot est déjà en favori
        $check = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND word_id = ?");
        $check->execute([$userId, $wordId]);

        if ($check->fetch()) {
            echo json_encode(['status' => 'already']);
            exit;
        }

        // Ajouter le mot aux favoris
        $insert = $pdo->prepare("INSERT INTO favorites (user_id, word_id, lang_code) VALUES (?, ?, ?)");
        $insert->execute([$userId, $wordId, 'all']); // 'all' parce qu'on ne précise pas la langue

        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>

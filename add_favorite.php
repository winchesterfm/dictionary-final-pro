<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['word']) && !empty($_POST['lang'])) {
    $word = trim($_POST['word']);
    $lang = trim($_POST['lang']);
    $userId = $_SESSION['user']['id'];

    // Chercher l'ID du mot
    $stmt = $pdo->prepare("SELECT id FROM words WHERE word = ?");
    $stmt->execute([$word]);
    $wordData = $stmt->fetch();

    if ($wordData) {
        $wordId = $wordData['id'];

        // Vérifier si déjà favori
        $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND word_id = ? AND lang_code = ?");
        $stmt->execute([$userId, $wordId, $lang]);

        if (!$stmt->fetch()) {
            // Ajouter
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, word_id, lang_code) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $wordId, $lang]);
        }
    }
}

header("Location: search.php?q=" . urlencode($_POST['q'] ?? ''));
exit;
?>

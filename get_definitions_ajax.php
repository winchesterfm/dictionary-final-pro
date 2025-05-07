<?php
require 'config.php';

if (!isset($_SESSION['user']) || empty($_GET['word_id'])) {
    echo 'Erreur.';
    exit;
}

$wordId = intval($_GET['word_id']);

$stmt = $pdo->prepare("
    SELECT lang_code, definition
    FROM definitions
    WHERE word_id = ?
");
$stmt->execute([$wordId]);
$definitions = $stmt->fetchAll();

if (empty($definitions)) {
    echo '<div class="text-muted">Aucune définition trouvée.</div>';
} else {
    foreach ($definitions as $def) {
        $language = [
            'fr' => '🇫🇷 Français',
            'en' => '🇬🇧 Anglais',
            'es' => '🇪🇸 Espagnol'
        ][$def['lang_code']] ?? strtoupper($def['lang_code']);

        echo '<div class="card mb-2 p-2 bg-light text-dark">
                <strong>' . $language . ' :</strong><br>' . htmlspecialchars($def['definition']) . '
              </div>';
    }
}
?>

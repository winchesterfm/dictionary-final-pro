<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$wordId = $_GET['id'] ?? null;
$wordId = filter_var($wordId, FILTER_VALIDATE_INT);
$redirect = $_GET['from'] ?? 'dashboard.php';
$userId = $_SESSION['user']['id'];

if (!$wordId) {
    echo '<div class="alert alert-danger text-center m-5">❌ Identifiant du mot manquant ou invalide.</div>';
    require 'footer.php';
    exit;
}

// Récupération du mot
$stmt = $pdo->prepare("SELECT * FROM words WHERE id = ?");
$stmt->execute([$wordId]);
$word = $stmt->fetch();

if (!$word) {
    echo '<div class="alert alert-danger text-center m-5">❌ Mot introuvable.</div>';
    require 'footer.php';
    exit;
}

// Récupération des définitions existantes
$stmt = $pdo->prepare("SELECT lang_code, definition FROM definitions WHERE word_id = ?");
$stmt->execute([$wordId]);
$definitions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newWord = trim($_POST['word']);
    $defFr = trim($_POST['definition_fr']);
    $defEn = trim($_POST['definition_en']);
    $defEs = trim($_POST['definition_es']);

    if (empty($newWord) || empty($defFr) || empty($defEn) || empty($defEs)) {
        $error = "❌ Tous les champs sont requis.";
    } else {
        try {
            $pdo->beginTransaction();

            // Mise à jour du mot
            $stmt = $pdo->prepare("UPDATE words SET word = ? WHERE id = ?");
            $stmt->execute([$newWord, $wordId]);

            // Mise à jour ou insertion des définitions
            $langs = ['fr' => $defFr, 'en' => $defEn, 'es' => $defEs];

            foreach ($langs as $lang => $def) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM definitions WHERE word_id = ? AND lang_code = ?");
                $check->execute([$wordId, $lang]);

                if ($check->fetchColumn() > 0) {
                    $update = $pdo->prepare("UPDATE definitions SET definition = ? WHERE word_id = ? AND lang_code = ?");
                    $update->execute([$def, $wordId, $lang]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO definitions (word_id, lang_code, definition) VALUES (?, ?, ?)");
                    $insert->execute([$wordId, $lang, $def]);
                }
            }

            $pdo->commit();
            log_action($pdo, $userId, "Modification du mot ID $wordId", $_SERVER['REMOTE_ADDR']);
            $success = "✅ Mot mis à jour avec succès !";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>

<main class="container py-5">
    <div class="text-center mb-4">
        <h2>✏️ Modifier le mot</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" class="fade-in" style="max-width: 600px; margin: auto;">
        <div class="mb-3">
            <label class="form-label">Mot</label>
            <input type="text" name="word" class="form-control" value="<?= htmlspecialchars($word['word']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Définition 🇫🇷 (Français)</label>
            <textarea name="definition_fr" class="form-control" rows="2" required><?= htmlspecialchars($definitions['fr'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Définition 🇬🇧 (Anglais)</label>
            <textarea name="definition_en" class="form-control" rows="2" required><?= htmlspecialchars($definitions['en'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Définition 🇪🇸 (Espagnol)</label>
            <textarea name="definition_es" class="form-control" rows="2" required><?= htmlspecialchars($definitions['es'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= htmlspecialchars($redirect) . (isset($_GET['user_id']) ? '?user_id=' . (int)$_GET['user_id'] : '') ?>" class="btn btn-secondary">
                ⬅️ Annuler
            </a>
            <button type="submit" class="btn btn-success">💾 Enregistrer</button>
        </div>
    </form>
</main>

<?php require 'footer.php'; ?>

<!-- Styles -->
<style>
.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>

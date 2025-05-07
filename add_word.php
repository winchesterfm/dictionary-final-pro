<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['editor', 'admin'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mot = trim($_POST['mot']);
    $fr = trim($_POST['definition_fr']);
    $en = trim($_POST['definition_en']);
    $es = trim($_POST['definition_es']);
    $userId = $_SESSION['user']['id'];

    if ($mot && $fr && $en && $es) {
        try {
            $pdo->beginTransaction();

            // Insérer le mot
            $stmt = $pdo->prepare("INSERT INTO words (word, created_by) VALUES (?, ?)");
            $stmt->execute([$mot, $userId]);
            $wordId = $pdo->lastInsertId();

            // Insérer les définitions
            $defs = [
                ['lang_code' => 'fr', 'definition' => $fr],
                ['lang_code' => 'en', 'definition' => $en],
                ['lang_code' => 'es', 'definition' => $es],
            ];
            foreach ($defs as $def) {
                $stmt = $pdo->prepare("INSERT INTO definitions (word_id, lang_code, definition) VALUES (?, ?, ?)");
                $stmt->execute([$wordId, $def['lang_code'], $def['definition']]);
            }

            // Log action
            log_action($pdo, $userId, 'ajout_mot', "Ajout du mot \"$mot\" avec ses définitions");

            $pdo->commit();
            $success = "✅ Le mot a été ajouté avec succès.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Erreur : " . $e->getMessage();
        }
    } else {
        $error = "❌ Tous les champs sont obligatoires.";
    }
}
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow">
                <h3 class="text-center mb-4">➕ Ajouter un mot</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success text-center"><?= $success ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger text-center"><?= $error ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="mot" class="form-label">Mot</label>
                        <input type="text" class="form-control" name="mot" id="mot" required>
                    </div>

                    <div class="mb-3">
                        <label for="definition_fr" class="form-label">Définition (Français)</label>
                        <textarea class="form-control" name="definition_fr" id="definition_fr" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="definition_en" class="form-label">Definition (Anglais)</label>
                        <textarea class="form-control" name="definition_en" id="definition_en" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="definition_es" class="form-label">Definición (Espagnol)</label>
                        <textarea class="form-control" name="definition_es" id="definition_es" rows="2" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

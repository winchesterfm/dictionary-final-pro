<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$success = $error = "";

// Gestion du changement de mot de passe
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $error = "❌ Les mots de passe ne correspondent pas.";
    } elseif (strlen($new) < 6) {
        $error = "🔒 Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset = 0 WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        $_SESSION['user']['password_reset'] = 0;
        $success = "✅ Mot de passe mis à jour avec succès.";
    }
}
?>

<main class="d-flex">
    <?php require 'sidebar.php'; ?>

    <div class="content">
        <div class="container d-flex justify-content-center">
            <div class="card shadow p-4 mt-4 w-100" style="max-width: 600px;">
                <h3 class="text-center mb-4">👤 Mon profil</h3>

                <p><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Rôle :</strong> <?= ucfirst($user['role']) ?></p>

                <hr>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <h5 class="mt-3">🔐 Modifier mon mot de passe</h5>

                <form method="post">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

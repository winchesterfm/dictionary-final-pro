<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];
$username = htmlspecialchars($user['username']);
$success = '';
$error = '';

// Traitement du formulaire de changement de mot de passe
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['new_password'])) {
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset = 0 WHERE id = ?");
    if ($stmt->execute([$newPassword, $userId])) {
        $_SESSION['user']['password_reset'] = 0;
        $success = "✅ Mot de passe mis à jour avec succès.";
        log_action($pdo, $userId, "Changement du mot de passe", $_SERVER['REMOTE_ADDR']);
    } else {
        $error = "❌ Une erreur s'est produite lors de la mise à jour.";
    }
}
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h3 class="text-center mb-4">👤 Mon profil</h3>

                <p><strong>Nom d'utilisateur :</strong> <?= $username ?></p>
                <p><strong>Rôle :</strong> <?= ucfirst($user['role']) ?></p>

                <hr>
                <h5 class="mb-3">🔒 Changer mon mot de passe</h5>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

<!-- Afficher/Masquer mot de passe -->
<script>
function togglePassword() {
    const input = document.getElementById("new_password");
    input.type = input.type === "password" ? "text" : "password";
}
</script>

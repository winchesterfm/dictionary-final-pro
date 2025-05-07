<?php
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$userData = null;

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$userId = intval($_GET['id']);

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

if (!$userData) {
    header("Location: users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    // 🔁 Réinitialisation du mot de passe si demandé
    if (isset($_POST['reset_password'])) {
        $newPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset = 1 WHERE id = ?");
        $stmt->execute([$newPassword, $userId]);

        // Log
        $adminId = $_SESSION['user']['id'];
        log_action($pdo, $adminId, 'reinitialisation_mdp', "Mot de passe réinitialisé pour \"$username\"");

        $success = "🔐 Mot de passe réinitialisé avec succès (nouveau : 123456)";
    } else {
        // 🎯 Mise à jour des données
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $userId]);

        $adminId = $_SESSION['user']['id'];
        log_action($pdo, $adminId, 'modification_utilisateur', "Modification de l'utilisateur \"$username\" (nouveau rôle : $role)");

        $success = "✅ Utilisateur mis à jour avec succès.";
    }

    // Recharger les données
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
}
?>

<?php require 'header.php'; ?>

<main class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 500px;">
        <h3 class="text-center mb-4">✏️ Modifier l'utilisateur</h3>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($userData['username']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-select" required>
                    <option value="viewer" <?= $userData['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                    <option value="editor" <?= $userData['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">💾 Enregistrer</button>
        </form>

        <!-- 🔐 Bouton réinitialisation -->
        <form method="post">
            <input type="hidden" name="username" value="<?= htmlspecialchars($userData['username']) ?>">
            <input type="hidden" name="role" value="<?= $userData['role'] ?>">
            <input type="hidden" name="reset_password" value="1">
            <button type="submit" class="btn btn-warning w-100">🔁 Réinitialiser le mot de passe</button>
        </form>
    </div>
</main>

<?php require 'footer.php'; ?>

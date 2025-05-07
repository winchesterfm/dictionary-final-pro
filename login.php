<?php
require 'config.php';
require 'header.php';

$error = '';

// Rediriger si déjà connecté
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['password_reset']) {
        header("Location: profile.php");
        exit;
    }
    switch ($_SESSION['user']['role']) {
        case 'admin':
            header("Location: dashboard.php");
            break;
        case 'editor':
            header("Location: dashboard_editor.php");
            break;
        case 'viewer':
            header("Location: index.php");
            break;
    }
    exit;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = $user;

        // ✅ Log de connexion
        log_action($pdo, $user['id'], 'connexion', "Connexion réussie de l'utilisateur \"{$user['username']}\"");

        // Redirection selon le rôle
        if ($user['password_reset']) {
            header("Location: profile.php");
            exit;
        }
        switch ($user['role']) {
            case 'admin':
                header("Location: dashboard.php");
                break;
            case 'editor':
                header("Location: dashboard_editor.php");
                break;
            case 'viewer':
                header("Location: index.php");
                break;
        }
        exit;
    } else {
        $error = "❌ Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<main class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">🔐 Connexion</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
    </div>
</main>

<?php require 'footer.php'; ?>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}
</script>

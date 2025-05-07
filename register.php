<?php
// register.php
require 'config.php';
require 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si déjà connecté, on redirige vers le bon tableau de bord
if (!empty($_SESSION['user'])) {
    switch ($_SESSION['user']['role']) {
        case 'admin':
            header('Location: dashboard.php');
            break;
        case 'editor':
            header('Location: dashboard_editor.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

$error   = '';
$success = '';

// On prépare un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérif CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $error = '⛔ Formulaire invalide, veuillez réessayer.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $pass1    = $_POST['password'] ?? '';
        $pass2    = $_POST['confirm_password'] ?? '';

        // Validation basique
        if ($username === '' || $pass1 === '' || $pass2 === '') {
            $error = '❌ Tous les champs sont obligatoires.';
        } elseif ($pass1 !== $pass2) {
            $error = '❌ Les mots de passe ne correspondent pas.';
        } elseif (strlen($pass1) < 6) {
            $error = '❌ Le mot de passe doit faire au moins 6 caractères.';
        } else {
            // Vérifier unicité du nom d’utilisateur
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = '❌ Ce nom d’utilisateur est déjà pris.';
            } else {
                // Tout est bon → insertion
                $hash = password_hash($pass1, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare("INSERT INTO users (username, password, role, password_reset) VALUES (?, ?, 'viewer', 0)");
                $ins->execute([$username, $hash]);
                $success = '✅ Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.';
                // Optionnel : on peut rediriger vers login.php
                header('Refresh:2;url=login.php');
            }
        }
    }
}
?>

<main class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow p-4" style="width:100%;max-width:420px;">
        <h3 class="text-center mb-4">✏️ Créer un compte</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Nom d’utilisateur</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmez le mot de passe</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    required
                >
            </div>

            <button type="submit" class="btn btn-success w-100">Créer mon compte</button>
        </form>

        <p class="text-center mt-3">
            Vous avez déjà un compte ?
            <a href="login.php">Connectez-vous ici</a>.
        </p>
    </div>
</main>

<?php require 'footer.php'; ?>

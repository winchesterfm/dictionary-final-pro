<?php
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Vérifier si l'utilisateur existe déjà
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);

    if ($check->fetch()) {
        $error = "❌ Le nom d'utilisateur \"$username\" est déjà utilisé.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);

        // Log de création
        $adminId = $_SESSION['user']['id'];
        log_action($pdo, $adminId, 'ajout_utilisateur', "Création de l'utilisateur \"$username\" avec le rôle \"$role\"");

        header("Location: users.php");
        exit;
    }
}
?>

<?php require 'header.php'; ?>

<main class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 450px;">
        <h3 class="text-center mb-4">👤 Ajouter un utilisateur</h3>

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
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Rôle</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success w-100">Créer</button>
        </form>
    </div>
</main>

<?php require 'footer.php'; ?>

<script>
function togglePassword() {
    const passInput = document.getElementById("password");
    passInput.type = passInput.type === "password" ? "text" : "password";
}
</script>

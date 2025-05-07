<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Recherche utilisateur
$search = $_GET['search'] ?? '';

// Récupérer les utilisateurs
$sql = "SELECT * FROM users WHERE username LIKE ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['%' . $search . '%']);
$users = $stmt->fetchAll();
?>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>👥 Gestion des utilisateurs</h2>
        <a href="add_user.php" class="btn btn-success">➕ Ajouter un utilisateur</a>
    </div>

    <!-- Formulaire de recherche -->
    <form method="get" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="🔍 Rechercher un utilisateur..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-outline-secondary">Rechercher</button>
        </div>
    </form>

    <?php if (empty($users)): ?>
        <div class="alert alert-info text-center">Aucun utilisateur trouvé.</div>
    <?php else: ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Rôle</th>
                        <th>Créé le</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= ucfirst($user['role']) ?></td>
                            <td><?= $user['created_at'] ?></td>
                            <td class="text-center">
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">✏️ Modifier</a>
                                <?php if ($_SESSION['user']['id'] != $user['id'] && $user['role'] !== 'admin'): ?>
                                    <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">🗑️ Supprimer</a>
                                <?php else: ?>
                                    <span class="text-muted">🔒</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

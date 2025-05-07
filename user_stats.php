<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Requête globale : on récupère les utilisateurs et leurs stats liées
$users = $pdo->query("
    SELECT u.id, u.username, u.role,
        (SELECT COUNT(*) FROM words w WHERE w.created_by = u.id) AS word_count,
        (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorite_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<main class="d-flex">
    <?php require 'sidebar.php'; ?>

    <div class="content">
        <div class="container d-flex justify-content-center">
            <div class="card shadow p-4 mt-4 w-100" style="max-width: 850px;">
                <h3 class="text-center mb-4">📊 Statistiques des utilisateurs</h3>

                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nom d'utilisateur</th>
                            <th>Rôle</th>
                            <th>Mots ajoutés</th>
                            <th>Favoris</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'editor' ? 'primary' : 'secondary') ?>">
                                    <?= ucfirst($u['role']) ?></span></td>
                                <td><?= $u['word_count'] ?></td>
                                <td><?= $u['favorite_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Récupérer les logs de recherche
$stmt = $pdo->query("
    SELECT s.id, s.term, s.searched_at, u.username 
    FROM search_logs s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.searched_at DESC
");
$logs = $stmt->fetchAll();
?>

<main class="container py-5">
    <h2 class="mb-4">📄 Historique des recherches</h2>

    <?php if (empty($logs)): ?>
        <div class="alert alert-info text-center">Aucune recherche enregistrée pour le moment.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Mot recherché</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($log['username']) ?></td>
                            <td><?= htmlspecialchars($log['term']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($log['searched_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

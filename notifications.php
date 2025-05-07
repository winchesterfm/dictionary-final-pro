<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// Récupération des notifications
$stmt = $pdo->prepare("
    SELECT id, message, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Groupement par mois
$grouped = [];
foreach ($notifications as $notif) {
    $month = date('F Y', strtotime($notif['created_at']));
    $grouped[$month][] = $notif;
}
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">🔔 Mes notifications</h2>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info text-center">Aucune notification pour le moment.</div>
    <?php else: ?>
        <?php foreach ($grouped as $month => $notifs): ?>
            <h5 class="text-primary mt-4"><?= $month ?></h5>
            <div class="row g-3">
                <?php foreach ($notifs as $notif): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm p-3 fade-in hover-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="me-2">
                                    <div class="fw-bold">📩 <?= htmlspecialchars($notif['message']) ?></div>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                                </div>
                                <!-- Supprimer notification si souhaité
                                <form method="post" action="delete_notification.php">
                                    <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer">🗑️</button>
                                </form>
                                -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<!-- Styles personnalisés -->
<style>
.fade-in {
    animation: fadeIn 0.6s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hover-card {
    transition: transform 0.2s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
.dark-mode .hover-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(255, 255, 255, 0.1);
}
</style>

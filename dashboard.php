<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$username = htmlspecialchars($_SESSION['user']['username']);

// Statistiques
$totalWords = $pdo->prepare("SELECT COUNT(*) FROM words WHERE created_by = ?");
$totalWords->execute([$userId]);
$wordCount = $totalWords->fetchColumn();

$totalFavorites = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$totalFavorites->execute([$userId]);
$favCount = $totalFavorites->fetchColumn();

// Logs
log_action($pdo, $userId, "Accès au tableau de bord (admin)", $_SERVER['REMOTE_ADDR']);
?>

<main class="container py-5">
    <div class="text-center mb-5 fade-in">
        <h2 class="mb-3">👋 Bienvenue, <?= $username ?> !</h2>
        <p class="lead text-muted">Voici votre tableau de bord administrateur.</p>
    </div>

    <div class="row g-4 justify-content-center fade-in">
        <div class="col-md-4">
            <a href="add_word.php" class="text-decoration-none">
                <div class="card shadow text-center p-4 hover-card">
                    <i class="fas fa-plus-circle fa-2x mb-2 text-primary"></i>
                    <h5>Ajouter un mot</h5>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="users.php" class="text-decoration-none">
                <div class="card shadow text-center p-4 hover-card">
                    <i class="fas fa-users fa-2x mb-2 text-success"></i>
                    <h5>Utilisateurs</h5>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="stats.php" class="text-decoration-none">
                <div class="card shadow text-center p-4 hover-card">
                    <i class="fas fa-chart-bar fa-2x mb-2 text-info"></i>
                    <h5>Statistiques</h5>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="notifications.php" class="text-decoration-none">
                <div class="card shadow text-center p-4 hover-card">
                    <i class="fas fa-bell fa-2x mb-2 text-warning"></i>
                    <h5>Notifications</h5>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="favorites.php" class="text-decoration-none">
                <div class="card shadow text-center p-4 hover-card">
                    <i class="fas fa-star fa-2x mb-2 text-secondary"></i>
                    <h5>Favoris : <?= $favCount ?></h5>
                </div>
            </a>
        </div>

        <div class="col-md-4">
    <a href="user_words.php?user_id=<?= $userId ?>" class="text-decoration-none">
        <div class="card shadow text-center p-4 hover-card">
            <i class="fas fa-book fa-2x mb-2 text-success"></i>
            <h5>Mots ajoutés : <?= $wordCount ?></h5>
        </div>
    </a>
</div>

    </div>
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

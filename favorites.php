<?php 
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// Récupérer les favoris avec mots et définitions
$stmt = $pdo->prepare("
    SELECT f.id AS fav_id, w.word, w.id AS word_id, w.created_at, d.lang_code, d.definition
    FROM favorites f
    JOIN words w ON f.word_id = w.id
    JOIN definitions d ON w.id = d.word_id
    WHERE f.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$data = $stmt->fetchAll();

$grouped = [];

// Organiser par mois
foreach ($data as $item) {
    $month = date('F Y', strtotime($item['created_at']));
    $word = $item['word'];
    $grouped[$month][$word]['definitions'][$item['lang_code']] = $item['definition'];
    $grouped[$month][$word]['word_id'] = $item['word_id'];
}
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">⭐ Mes favoris</h2>

    <?php if (empty($grouped)): ?>
        <div class="alert alert-info text-center">Vous n'avez encore aucun mot en favori.</div>
    <?php else: ?>
        <?php foreach ($grouped as $month => $words): ?>
            <h4 class="text-primary"><?= $month ?></h4>
            <div class="accordion mb-4" id="accordion-<?= md5($month) ?>">
                <?php foreach ($words as $word => $details): ?>
                    <div class="accordion-item mb-2 shadow-sm">
                        <h2 class="accordion-header" id="heading-<?= $details['word_id'] ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $details['word_id'] ?>">
                                <?= htmlspecialchars($word) ?>
                            </button>
                        </h2>
                        <div id="collapse-<?= $details['word_id'] ?>" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <?php foreach ($details['definitions'] as $lang => $def): ?>
                                    <p>
                                        <span class="badge bg-<?= 
                                            $lang === 'fr' ? 'primary' : (
                                            $lang === 'en' ? 'success' : (
                                            $lang === 'es' ? 'warning text-dark' : 'secondary')
                                        ) ?>">
                                            <?= $lang === 'fr' ? '🇫🇷 Français' : ($lang === 'en' ? '🇬🇧 Anglais' : '🇪🇸 Espagnol') ?>
                                        </span><br>
                                        <?= nl2br(htmlspecialchars($def)) ?>
                                    </p>
                                <?php endforeach; ?>

                                <form method="post" action="remove_favorite.php" class="mt-2">
                                    <input type="hidden" name="word_id" value="<?= $details['word_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger bounce-on-click">🗑️ Retirer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<!-- Styles -->
<style>
.bounce-on-click {
    transition: transform 0.2s;
}
.bounce-on-click:active {
    transform: scale(0.95);
}
.badge {
    transition: transform 0.2s ease, opacity 0.2s ease;
    cursor: default;
}
.badge:hover {
    transform: scale(1.1);
    opacity: 0.9;
}
</style>

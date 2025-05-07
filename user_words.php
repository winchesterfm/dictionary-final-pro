<?php
require 'config.php';
require 'header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_GET['user_id'] ?? $_SESSION['user']['id'];
$userId = filter_var($userId, FILTER_VALIDATE_INT);

if (!$userId) {
    echo '<div class="alert alert-danger text-center m-5">❌ Identifiant d’utilisateur manquant ou invalide.</div>';
    require 'footer.php';
    exit;
}

// Rechercher l'utilisateur
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="alert alert-danger text-center m-5">❌ Utilisateur introuvable.</div>';
    require 'footer.php';
    exit;
}

$username = htmlspecialchars($user['username']);

// Suppression
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_word_id'])) {
    $deleteId = (int) $_POST['delete_word_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM definitions WHERE word_id = ?")->execute([$deleteId]);
        $pdo->prepare("DELETE FROM favorites WHERE word_id = ?")->execute([$deleteId]);
        $pdo->prepare("DELETE FROM words WHERE id = ?")->execute([$deleteId]);
        $pdo->commit();
        log_action($pdo, $_SESSION['user']['id'], "Suppression du mot ID $deleteId", $_SERVER['REMOTE_ADDR']);
        echo "<script>setTimeout(() => location.href = location.href, 500);</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger text-center">❌ Erreur lors de la suppression.</div>';
    }
}

// Récupération des mots
$stmt = $pdo->prepare("SELECT id, word, created_at FROM words WHERE created_by = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$words = $stmt->fetchAll();
?>

<main class="container py-5">
    <div class="mb-4 text-center">
        <h2 class="fade-in">📚 Mots ajoutés par <strong><?= $username ?></strong></h2>
        <p class="text-muted">Liste complète des mots ajoutés par cet utilisateur.</p>
    </div>

    <?php if (empty($words)): ?>
        <div class="alert alert-info text-center">Aucun mot ajouté pour le moment.</div>
    <?php else: ?>
        <!-- Barre de recherche -->
        <form class="row g-2 mb-4 fade-in" onsubmit="return false;">
    <div class="col-md-10">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 Rechercher un mot...">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Rechercher</button>
    </div>
</form>


        <div class="table-responsive fade-in">
            <table class="table table-hover align-middle" id="wordsTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Mot</th>
                        <th>Date d’ajout</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($words as $index => $row): ?>
                        <tr class="hover-highlight">
                            <td><?= $index + 1 ?></td>
                            <td class="word-col"><strong><?= htmlspecialchars($row['word']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td class="d-flex gap-2">
                                <a href="edit_word.php?id=<?= $row['id'] ?>&from=user_words.php&user_id=<?= $userId ?>" class="btn btn-sm btn-outline-primary bounce-on-hover">✏️ Modifier</a>
                                <form method="post" onsubmit="return confirm('❗ Confirmer la suppression de ce mot ?')">
                                    <input type="hidden" name="delete_word_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger bounce-on-hover">🗑️ Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<!-- Style -->
<style>
.fade-in {
    animation: fadeIn 0.6s ease-in-out;
}
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
}

.bounce-on-hover {
    transition: transform 0.2s ease;
}
.bounce-on-hover:hover {
    transform: scale(1.05);
}

.hover-highlight:hover {
    background-color: rgba(0, 123, 255, 0.05);
}
</style>

<!-- Script recherche -->
<script>
document.getElementById('searchInput').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#wordsTable tbody tr').forEach(function (row) {
        const word = row.querySelector('.word-col').textContent.toLowerCase();
        row.style.display = word.includes(query) ? '' : 'none';
    });
});
</script>

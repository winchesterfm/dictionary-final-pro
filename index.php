<?php
// index.php
require 'config.php';
require 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Fonction pour récupérer le mot du jour
function getWordOfTheDay(PDO $pdo): array {
    $today = date('Y-m-d');
    $icons = ['📘','📙','📕','📗','📓','📒','📚'];
    $icon = $icons[crc32($today) % count($icons)];
    $count = (int)$pdo->query("SELECT COUNT(*) FROM words")->fetchColumn();
    if ($count === 0) {
        return [null, [], $icon];
    }
    $index = crc32($today) % $count;
    $stmt = $pdo->prepare("SELECT id, word FROM words ORDER BY id LIMIT 1 OFFSET :idx");
    $stmt->bindValue(':idx', $index, PDO::PARAM_INT);
    $stmt->execute();
    $word = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$word) {
        return [null, [], $icon];
    }
    $dstmt = $pdo->prepare("SELECT lang_code, definition FROM definitions WHERE word_id = ?");
    $dstmt->execute([$word['id']]);
    $defs = $dstmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
    return [$word, $defs, $icon];
}

// Fonction pour récupérer les mots populaires
function getPopularWords(PDO $pdo, int $limit = 6): array {
    $stmt = $pdo->prepare("
      SELECT searched_word AS word, COUNT(*) AS cnt
      FROM search_logs
      GROUP BY searched_word
      ORDER BY cnt DESC
      LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour vérifier si un mot est en favori
function isFavorite(PDO $pdo, int $wordId, int $userId): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE word_id = ? AND user_id = ?");
    $stmt->execute([$wordId, $userId]);
    return (bool)$stmt->fetchColumn();
}

// Récupération du mot du jour
list($wordOfTheDay, $definitions, $icon) = getWordOfTheDay($pdo);

// Récupération des mots populaires
$popularWords = getPopularWords($pdo);

// Vérification de l'état favori du mot du jour
$isFav = false;
if ($wordOfTheDay && !empty($_SESSION['user'])) {
    $isFav = isFavorite($pdo, $wordOfTheDay['id'], $_SESSION['user']['id']);
}

// Gestion AJAX pour le toggle des favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['api'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (empty($_SESSION['user']) || !hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        echo json_encode(['success'=>false,'message'=>'Non autorisé']);
        exit;
    }
    $wid = intval($_POST['word_id'] ?? 0);
    $uid = $_SESSION['user']['id'];
    if ($wid < 1) {
        echo json_encode(['success'=>false,'message'=>'ID invalide']);
        exit;
    }
    // Vérification de l'existence du mot
    $check = $pdo->prepare("SELECT id FROM words WHERE id = ?");
    $check->execute([$wid]);
    if (!$check->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Mot introuvable']);
        exit;
    }
    // Toggle favori
    $fav = $pdo->prepare("SELECT id FROM favorites WHERE word_id = ? AND user_id = ?");
    $fav->execute([$wid,$uid]);
    if ($fav->fetch()) {
        $pdo->prepare("DELETE FROM favorites WHERE word_id = ? AND user_id = ?")
            ->execute([$wid,$uid]);
        echo json_encode(['success'=>true,'action'=>'removed']);
    } else {
        $pdo->prepare("INSERT INTO favorites(word_id,user_id,created_at) VALUES(?,?,NOW())")
            ->execute([$wid,$uid]);
        echo json_encode(['success'=>true,'action'=>'added']);
    }
    exit;
}
?>

<main class="container py-5 text-center">

  <!-- Titre principal -->
  <h1 class="display-4 fw-bold text-gradient fade-in">📚 Bienvenue dans le Dictionnaire Multilingue</h1>
  <p class="lead mb-4 fade-in" style="animation-delay:.3s;">
    🔍 Explorez des mots en français, anglais et espagnol.<br>
    ⭐ Connectez-vous pour gérer vos favoris et définitions.
  </p>

  <!-- Barre de recherche -->
  <div class="row justify-content-center mb-4 fade-in" style="animation-delay:.6s;">
    <div class="col-md-6 position-relative">
      <div class="input-group input-group-lg shadow-sm">
        <input id="quickSearch" type="text" class="form-control" placeholder="Rechercher un mot…" autocomplete="off">
        <button id="btnSearch" class="btn btn-primary"><i class="fas fa-search"></i></button>
      </div>
      <div id="searchSuggestions" class="search-suggestions"></div>
    </div>
  </div>

  <!-- Mot du jour -->
  <?php if ($wordOfTheDay): ?>
  <div class="card mot-du-jour mx-auto mb-5 animated-card fade-in-slow" style="max-width:700px;">
    <div class="card-body">
      <h3 class="card-title mb-3 pulse-word"><?= $icon ?> Mot du jour : <strong><?= htmlspecialchars($wordOfTheDay['word']) ?></strong></h3>
      <?php foreach (['fr'=>'🇫🇷','en'=>'🇬🇧','es'=>'🇪🇸'] as $code=>$flag):
        if (!empty($definitions[$code])): ?>
        <div class="mb-3 text-start">
          <span class="badge bg-<?= $code==='fr'?'primary':($code==='en'?'success':'warning text-dark') ?>">
            <?= $flag.' '.($code==='fr'?'Français':($code==='en'?'Anglais':'Espagnol')) ?>
          </span>
          <p class="mt-1"><?= nl2br(htmlspecialchars($definitions[$code][0])) ?></p>
        </div>
      <?php endif; endforeach; ?>

      <div class="d-flex justify-content-between align-items-center">
        <button id="favBtn" class="btn btn-sm <?= $isFav?'btn-warning':'btn-outline-secondary' ?>"
                data-word-id="<?= $wordOfTheDay['id'] ?>">
          <i class="<?= $isFav?'fas text-dark':'far' ?> fa-star"></i>
        </button>
        <a href="search.php?q=<?= urlencode($wordOfTheDay['word']) ?>"
           class="btn btn-outline-primary btn-sm">🔍 Voir plus</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Mots populaires -->
  <h4 class="mb-3 text-start"><i class="fas fa-fire text-danger"></i> Mots populaires</h4>
  <div class="row g-2 mb-5">
    <?php if (empty($popularWords)): ?>
      <p class="text-muted">Aucun mot populaire pour le moment.</p>
    <?php else: foreach ($popularWords as $w): ?>
      <div class="col-6 col-md-4">
        <a href="search.php?q=<?= urlencode($w['word']) ?>"
           class="btn btn-sm btn-outline-primary w-100 text-truncate">
          <?= htmlspecialchars($w['word']) ?>
          <span class="badge bg-light text-dark"><?= $w['cnt'] ?></span>
        </a>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Appel à l'action -->
  <div class="cta-section p-4 rounded-3 bg-gradient text-white mb-5 animated-card fade-in-slow">
    <h2>Prêt à enrichir votre vocabulaire&nbsp;?</h2>
    <?php if (!isset($_SESSION['user'])): ?>
      <a href="register.php" class="btn btn-lg btn-light mt-3 glow-button-green">🚀 Créer un compte

 
<?php endif; ?>
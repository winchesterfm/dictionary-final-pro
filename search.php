<?php
require 'config.php';
require 'header.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Suggestions AJAX
if (isset($_GET['suggest'], $_GET['q']) && $_GET['suggest'] === '1') {
    header('Content-Type: application/json');
    $term = trim($_GET['q']);
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT word FROM words WHERE word LIKE ? ORDER BY CHAR_LENGTH(word), word LIMIT 10");
        $stmt->execute(["%$term%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

// Fonctions
function isFavorite($pdo, $wid, $uid) {
    $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE word_id=? AND user_id=?");
    $stmt->execute([$wid, $uid]);
    return (bool) $stmt->fetchColumn();
}
function logSearch($pdo, $term, $uid = null) {
    if (!$uid) return; // on ne log que si l'utilisateur est connecté
    $stmt = $pdo->prepare("INSERT INTO search_logs (user_id, searched_word, search_time) VALUES (?, ?, NOW())");
    $stmt->execute([$uid, $term]);
}
function formatNumber($n) {
    return number_format($n, 0, ',', ' ');
}

// Recherche
$term = trim($_GET['q'] ?? '');
$lang = $_GET['lang'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total = 0;
$pages = 0;
$results = [];

if ($term !== '') {
    $where = strlen($term) >= 3 ? "w.word LIKE :term" : "w.word LIKE :term_prefix";
    $params = strlen($term) >= 3 ? [':term' => "%$term%"] : [':term_prefix' => "$term%"];
    if ($lang !== 'all') {
        $where .= " AND d.lang_code = :lang";
        $params[':lang'] = $lang;
    }

    $count = $pdo->prepare("SELECT COUNT(DISTINCT w.id) FROM words w JOIN definitions d ON d.word_id = w.id WHERE $where");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = ceil($total / $limit);

    $sql = "
        SELECT w.id, w.word, d.lang_code, d.definition
        FROM words w
        JOIN definitions d ON d.word_id = w.id
        WHERE $where
        ORDER BY 
            CASE WHEN w.word = :exact THEN 0
                 WHEN w.word LIKE :starts THEN 1
                 ELSE 2
            END,
            w.word ASC
        LIMIT :lim OFFSET :off
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':exact', $term);
    $stmt->bindValue(':starts', "$term%");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        $wid = $r['id'];
        if (!isset($results[$wid])) {
            $results[$wid] = [
                'word' => $r['word'],
                'definitions' => [],
                'is_favorite' => false
            ];
        }
        $results[$wid]['definitions'][$r['lang_code']][] = $r['definition'];
    }

    $uid = $_SESSION['user']['id'] ?? null;
    logSearch($pdo, $term, $uid);
    if ($uid) {
        foreach (array_keys($results) as $wid) {
            $results[$wid]['is_favorite'] = isFavorite($pdo, $wid, $uid);
        }
    }
}
?>

<main class="container py-5">
  <h2 class="mb-4 text-center"><i class="fas fa-search me-2"></i> Recherche</h2>

  <form method="get" id="searchForm" class="row g-3 align-items-end mb-4">
    <div class="col-md-6 position-relative">
      <input type="text" name="q" id="searchInput" class="form-control form-control-lg" placeholder="Entrez un mot..." value="<?= htmlspecialchars($term) ?>" autocomplete="off" required>
      <div id="suggestionList" class="list-group position-absolute w-100" style="z-index:1000; display:none;"></div>
    </div>
    <div class="col-md-3">
      <select name="lang" class="form-select form-select-lg">
        <option value="all" <?= $lang == 'all' ? 'selected' : '' ?>>🌐 Toutes les langues</option>
        <option value="fr"  <?= $lang == 'fr'  ? 'selected' : '' ?>>🇫🇷 Français</option>
        <option value="en"  <?= $lang == 'en'  ? 'selected' : '' ?>>🇬🇧 Anglais</option>
        <option value="es"  <?= $lang == 'es'  ? 'selected' : '' ?>>🇪🇸 Espagnol</option>
      </select>
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary btn-lg w-100">🔍 Rechercher</button>
    </div>
  </form>

  <?php if ($term): ?>
    <h5 class="text-center mb-4"><?= formatNumber($total) ?> résultat<?= $total>1?'s':'' ?> pour : <mark><?= htmlspecialchars($term) ?></mark></h5>

    <?php if (empty($results)): ?>
      <div class="alert alert-warning text-center">Aucun résultat trouvé.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($results as $wid => $data): ?>
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center <?= $data['word']===$term ? 'bg-primary text-white' : '' ?>">
              <h5 class="mb-0"><?= htmlspecialchars($data['word']) ?></h5>
              <?php if (isset($_SESSION['user'])): ?>
              <button class="btn btn-sm btn-icon favorite-btn <?= $data['is_favorite'] ? 'active' : '' ?>" data-wid="<?= $wid ?>">
                <i class="<?= $data['is_favorite'] ? 'fas' : 'far' ?> fa-star"></i>
              </button>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <?php foreach ($data['definitions'] as $code => $defs): ?>
                <span class="badge bg-<?= $code==='fr'?'primary':($code==='en'?'success':'warning text-dark') ?>">
                  <?= $code==='fr'?'🇫🇷 Français':($code==='en'?'🇬🇧 Anglais':'🇪🇸 Espagnol') ?>
                </span>
                <?php foreach ($defs as $def): ?>
                  <p class="mt-2"><?= nl2br(htmlspecialchars($def)) ?></p>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?q=<?= urlencode($term) ?>&lang=<?= $lang ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('searchInput');
  const list = document.getElementById('suggestionList');
  let timer;

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const val = input.value.trim();
    if (val.length < 2) return list.style.display = 'none';

    timer = setTimeout(() => {
      fetch(`search.php?q=${encodeURIComponent(val)}&suggest=1`)
        .then(r => r.json())
        .then(data => {
          if (!data.length) return list.style.display = 'none';
          list.innerHTML = data.map(word => `<button type="button" class="list-group-item list-group-item-action">${word}</button>`).join('');
          list.style.display = 'block';
          document.querySelectorAll('#suggestionList button').forEach(btn => {
            btn.onclick = () => {
              input.value = btn.textContent;
              list.style.display = 'none';
              document.getElementById('searchForm').submit();
            };
          });
        });
    }, 250);
  });

  document.addEventListener('click', e => {
    if (!list.contains(e.target) && e.target !== input) {
      list.style.display = 'none';
    }
  });

  document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.onclick = () => {
      const wid = btn.dataset.wid;
      const icon = btn.querySelector('i');
      fetch('search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=fav&wid=${wid}&csrf=<?= $csrf ?>`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          icon.classList.toggle('fas');
          icon.classList.toggle('far');
        }
      });
    };
  });
});
</script>

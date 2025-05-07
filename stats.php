<?php
// stats.php - Tableau de bord de statistiques pour administrateurs
require 'config.php';
require 'header.php';

// ================================================
// VÉRIFICATION D'ACCÈS ET INITIALISATION
// ================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Protection CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ================================================
// FONCTIONS UTILITAIRES
// ================================================

/**
 * Formate un nombre avec séparateur de milliers
 * @param int $number Le nombre à formater
 * @return string Nombre formaté
 */
function formatNumber($number) {
    return number_format($number, 0, ',', ' ');
}

/**
 * Exporte des données au format CSV
 * @param array $rows Données à exporter
 * @param array $headers En-têtes de colonnes
 * @param string $filename Nom du fichier
 */
function exportCsv(array $rows, array $headers, string $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $out = fopen('php://output', 'w');
    // BOM pour Excel
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

/**
 * Récupère une date depuis les paramètres
 * @param string $k Nom du paramètre
 * @param string $def Valeur par défaut
 * @return string Date au format Y-m-d
 */
function getDateParam(string $k, string $def): string {
    return (!empty($_GET[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$k]))
         ? $_GET[$k]
         : $def;
}

/**
 * Récupère un numéro de page depuis les paramètres
 * @param string $k Nom du paramètre
 * @return int Numéro de page (minimum 1)
 */
function getPageParam(string $k): int {
    return max(1, intval($_GET[$k] ?? 1));
}

/**
 * Obtient la couleur appropriée pour un badge d'action
 * @param string $actionType Type d'action
 * @return string Classe de couleur Bootstrap
 */
function getActionBadgeClass(string $actionType): string {
    return match($actionType) {
        'create' => 'success',
        'update' => 'info',
        'delete' => 'danger',
        'login'  => 'primary',
        'logout' => 'secondary',
        'search' => 'warning',
        default  => 'secondary'
    };
}

// ================================================
// SYSTÈME DE CACHE
// ================================================
$cacheDir = 'cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheFile = "$cacheDir/stats_" . date('Y-m-d') . ".cache";
$useCache = false;
$cacheExpiry = 3600; // 1 heure
$refresh = isset($_GET['refresh']) && $_GET['refresh'] == 1;

// Vérifie si on peut utiliser le cache
if (!$refresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheExpiry)) {
    $useCache = true;
    $cachedData = unserialize(file_get_contents($cacheFile));
}

// ================================================
// TRAITEMENT DES EXPORTS
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['csrf_token'] ?? '') === $csrf) {
    try {
        switch ($_POST['export'] ?? '') {
            case 'search_details':
                $stmt = $pdo->query("
                    SELECT 
                      DATE(search_time) AS date,
                      TIME(search_time) AS time,
                      searched_word,
                      COALESCE(u.username,'Visiteur') AS user
                    FROM search_logs s
                    LEFT JOIN users u ON s.user_id = u.id
                    ORDER BY search_time DESC
                ");
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);
                exportCsv($rows, ['Date','Heure','Mot','Utilisateur'], 'recherches_'.date('Y-m-d').'.csv');
                break;
                
            case 'activity_logs':
                $stmt = $pdo->query("
                    SELECT 
                      DATE(a.created_at) AS date,
                      TIME(a.created_at) AS time,
                      COALESCE(u.username,'Système') AS user,
                      a.action_type,
                      a.description
                    FROM activity_logs a
                    LEFT JOIN users u ON a.user_id = u.id
                    ORDER BY a.created_at DESC
                ");
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);
                exportCsv($rows, ['Date','Heure','Utilisateur','Action','Description'], 'activites_'.date('Y-m-d').'.csv');
                break;

            case 'user_words':
                $stmt = $pdo->query("
                    SELECT 
                      u.username,
                      u.role,
                      COUNT(w.id) AS total
                    FROM users u
                    LEFT JOIN words w ON u.id = w.created_by
                    GROUP BY u.id
                    ORDER BY total DESC
                ");
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);
                exportCsv($rows, ['Utilisateur','Rôle','Total mots'], 'mots_par_utilisateur_'.date('Y-m-d').'.csv');
                break;
                
            case 'all_stats':
                // En-têtes Excel
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="statistiques_completes_'.date('Y-m-d').'.xls"');
                header('Cache-Control: max-age=0');
                
                echo '<!DOCTYPE html>';
                echo '<html>';
                echo '<head>';
                echo '<meta charset="UTF-8">';
                echo '<title>Statistiques</title>';
                echo '</head>';
                echo '<body>';
                
                // Statistiques globales
                echo '<h1>Statistiques globales</h1>';
                echo '<table border="1">';
                echo '<tr><th>Métrique</th><th>Valeur</th></tr>';
                echo '<tr><td>Utilisateurs</td><td>' . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . '</td></tr>';
                echo '<tr><td>Mots</td><td>' . $pdo->query("SELECT COUNT(*) FROM words")->fetchColumn() . '</td></tr>';
                echo '<tr><td>Définitions</td><td>' . $pdo->query("SELECT COUNT(*) FROM definitions")->fetchColumn() . '</td></tr>';
                echo '<tr><td>Favoris</td><td>' . $pdo->query("SELECT COUNT(*) FROM favorites")->fetchColumn() . '</td></tr>';
                echo '<tr><td>Recherches</td><td>' . $pdo->query("SELECT COUNT(*) FROM search_logs")->fetchColumn() . '</td></tr>';
                echo '</table>';
                
                // Autres sections...
                echo '</body>';
                echo '</html>';
                exit;
                break;
        }
    } catch (PDOException $e) {
        // Log l'erreur mais ne pas l'afficher à l'utilisateur
        error_log('Erreur lors de l\'export : ' . $e->getMessage());
    }
}

// ================================================
// PARAMÈTRES DE FILTRE ET PAGINATION
// ================================================
$startDate    = getDateParam('start_date', date('Y-m-d', strtotime('-7 days')));
$endDate      = getDateParam('end_date', date('Y-m-d'));
$searchFilter = $_GET['search_filter'] ?? '';
$actionFilter = $_GET['action_filter'] ?? '';
$userFilter   = $_GET['user_filter'] ?? '';
$searchPage   = getPageParam('search_page');
$actionsPage  = getPageParam('actions_page');
$perPage      = isset($_GET['per_page']) ? min(50, max(5, intval($_GET['per_page']))) : 10;
$offsetSearch  = ($searchPage  - 1) * $perPage;
$offsetActions = ($actionsPage - 1) * $perPage;

// ================================================
// RÉCUPÉRATION DES DONNÉES
// ================================================
try {
    if ($useCache) {
        // Utiliser les données en cache
        extract($cachedData);
    } else {
        // ----------------
        // STATISTIQUES GLOBALES
        // ----------------
        // En une seule requête pour optimiser
        $statsQuery = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM words) AS total_words,
                (SELECT COUNT(*) FROM definitions) AS total_defs,
                (SELECT COUNT(*) FROM favorites) AS total_favs,
                (SELECT COUNT(*) FROM search_logs) AS total_searches
        ");
        $globalStats = $statsQuery->fetch(PDO::FETCH_ASSOC);
        
        $totalUsers    = $globalStats['total_users'];
        $totalWords    = $globalStats['total_words'];
        $totalDefs     = $globalStats['total_defs'];
        $totalFavs     = $globalStats['total_favs'];
        $totalSearches = $globalStats['total_searches'];

        // ----------------
        // DONNÉES POUR GRAPHIQUES
        // ----------------
        // Rôles
        $userRoles = $pdo->query("
            SELECT role, COUNT(*) AS cnt 
            FROM users 
            GROUP BY role
            ORDER BY cnt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recherches par jour
        $chartStmt = $pdo->prepare("
            SELECT DATE(search_time) AS day, COUNT(*) AS cnt
            FROM search_logs
            WHERE DATE(search_time) BETWEEN :start_date AND :end_date
            GROUP BY day
            ORDER BY day ASC
        ");
        $chartStmt->bindParam(':start_date', $startDate);
        $chartStmt->bindParam(':end_date', $endDate);
        $chartStmt->execute();
        $searchData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Activité par type
        $actionTypes = $pdo->query("
            SELECT action_type, COUNT(*) AS cnt
            FROM activity_logs
            GROUP BY action_type
            ORDER BY cnt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Mise en cache des données statistiques
        $cacheData = [
            'totalUsers'    => $totalUsers,
            'totalWords'    => $totalWords,
            'totalDefs'     => $totalDefs,
            'totalFavs'     => $totalFavs,
            'totalSearches' => $totalSearches,
            'userRoles'     => $userRoles,
            'searchData'    => $searchData,
            'actionTypes'   => $actionTypes,
        ];
        
        file_put_contents($cacheFile, serialize($cacheData));
    }

    // ----------------
    // TABLES DE DÉTAIL (pas mises en cache car susceptibles de changer fréquemment)
    // ----------------
    
    // 1) Recherches détaillées (avec filtres) - CORRIGÉ: utilisation cohérente des paramètres nommés
    $searchQuery = "
        SELECT s.search_time, s.searched_word, COALESCE(u.username,'Visiteur') AS user
        FROM search_logs s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE 1=1
    ";
    $searchParams = [];
    
    if ($searchFilter) {
        $searchQuery .= " AND s.searched_word LIKE :search_filter";
        $searchParams[':search_filter'] = "%$searchFilter%";
    }
    
    if ($userFilter) {
        $searchQuery .= " AND (u.username LIKE :user_filter OR (:user_filter = 'Visiteur' AND u.username IS NULL))";
        $searchParams[':user_filter'] = $userFilter === 'Visiteur' ? 'Visiteur' : "%$userFilter%";
    }
    
    // Filtrer par date
    if ($startDate && $endDate) {
        $searchQuery .= " AND DATE(s.search_time) BETWEEN :start_date AND :end_date";
        $searchParams[':start_date'] = $startDate;
        $searchParams[':end_date'] = $endDate;
    }
    
    $searchQuery .= " ORDER BY s.search_time DESC";
    
    // Compter le total pour pagination
    $countQuery = str_replace("SELECT s.search_time, s.searched_word, COALESCE(u.username,'Visiteur') AS user", "SELECT COUNT(*)", $searchQuery);
    $countStmt = $pdo->prepare($countQuery);
    foreach ($searchParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalSearchLogCount = $countStmt->fetchColumn();
    $searchPages = ceil($totalSearchLogCount / $perPage);
    
    // Ajouter la pagination
    $searchQuery .= " LIMIT :limit OFFSET :offset";
    $searchLogStmt = $pdo->prepare($searchQuery);
    foreach ($searchParams as $key => $value) {
        $searchLogStmt->bindValue($key, $value);
    }
    $searchLogStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $searchLogStmt->bindValue(':offset', $offsetSearch, PDO::PARAM_INT);
    $searchLogStmt->execute();
    $searchLogs = $searchLogStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Historique actions (avec filtres) - CORRIGÉ: utilisation cohérente des paramètres nommés
    $actionQuery = "
        SELECT a.created_at, a.action_type, a.description, COALESCE(u.username,'Système') AS user
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1
    ";
    $actionParams = [];
    
    if ($actionFilter) {
        $actionQuery .= " AND a.action_type = :action_filter";
        $actionParams[':action_filter'] = $actionFilter;
    }
    
    if ($userFilter) {
        $actionQuery .= " AND (u.username LIKE :user_filter OR (:user_filter_system = 'Système' AND u.username IS NULL))";
        $actionParams[':user_filter'] = $userFilter === 'Système' ? 'Système' : "%$userFilter%";
        $actionParams[':user_filter_system'] = $userFilter;
    }
    
    // Filtrer par date 
    if ($startDate && $endDate) {
        $actionQuery .= " AND DATE(a.created_at) BETWEEN :start_date AND :end_date";
        $actionParams[':start_date'] = $startDate;
        $actionParams[':end_date'] = $endDate;
    }
    
    $actionQuery .= " ORDER BY a.created_at DESC";
    
    // Compter le total pour pagination
    $countQuery = str_replace("SELECT a.created_at, a.action_type, a.description, COALESCE(u.username,'Système') AS user", "SELECT COUNT(*)", $actionQuery);
    $countStmt = $pdo->prepare($countQuery);
    foreach ($actionParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalActionCount = $countStmt->fetchColumn();
    $actionPages = ceil($totalActionCount / $perPage);
    
    // Ajouter la pagination
    $actionQuery .= " LIMIT :limit OFFSET :offset";
    $actionStmt = $pdo->prepare($actionQuery);
    foreach ($actionParams as $key => $value) {
        $actionStmt->bindValue($key, $value);
    }
    $actionStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $actionStmt->bindValue(':offset', $offsetActions, PDO::PARAM_INT);
    $actionStmt->execute();
    $actionLogs = $actionStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Connexions utilisateurs
    $connStmt = $pdo->query("
        SELECT u.username, COUNT(l.id) AS total, MAX(l.login_time) AS last_login
        FROM users u
        LEFT JOIN login_logs l ON u.id = l.user_id
        GROUP BY u.id
        ORDER BY last_login DESC
    ");
    $connLogs = $connStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4) Mots par utilisateur
    $wordUserStmt = $pdo->query("
        SELECT u.username, u.role, COUNT(w.id) AS total
        FROM users u
        LEFT JOIN words w ON u.id = w.created_by
        GROUP BY u.id
        ORDER BY total DESC
    ");
    $wordUserLogs = $wordUserStmt->fetchAll(PDO::FETCH_ASSOC);

    // Liste des types d'actions pour les filtres
    $actionTypesList = $pdo->query("SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);
    
    // Liste d'utilisateurs pour les filtres
    $usersList = $pdo->query("SELECT username FROM users ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "Une erreur est survenue lors de la récupération des données: " . $e->getMessage();
    error_log($error);
}
?>

<main class="container py-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="fas fa-chart-line"></i> Tableau de bord
        </h1>
        <div class="btn-group">
            <a href="?refresh=1" class="btn btn-outline-primary">
                <i class="fas fa-sync-alt"></i> Actualiser
            </a>
            <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-file-export"></i> Exporter
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button class="dropdown-item" name="export" value="search_details">
                            <i class="fas fa-search me-2"></i> Recherches
                        </button>
                    </form>
                </li>
                <li>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button class="dropdown-item" name="export" value="activity_logs">
                            <i class="fas fa-history me-2"></i> Actions
                        </button>
                    </form>
                </li>
                <li>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button class="dropdown-item" name="export" value="user_words">
                            <i class="fas fa-book me-2"></i> Mots/utilisateurs
                        </button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button class="dropdown-item" name="export" value="all_stats">
                            <i class="fas fa-file-excel me-2"></i> Toutes les statistiques
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <!-- Statistiques globales -->
    <section class="mb-4 fade-in">
        <h2 class="section-title mb-3">
            <i class="fas fa-chart-pie me-2"></i> Statistiques globales
        </h2>
        <div class="row g-4">
            <?php
            $statItems = [
                ['icon' => 'fa-users', 'label' => 'Utilisateurs', 'value' => $totalUsers, 'color' => 'primary'],
                ['icon' => 'fa-book', 'label' => 'Mots', 'value' => $totalWords, 'color' => 'success'],
                ['icon' => 'fa-language', 'label' => 'Définitions', 'value' => $totalDefs, 'color' => 'info'],
                ['icon' => 'fa-star', 'label' => 'Favoris', 'value' => $totalFavs, 'color' => 'warning'],
                ['icon' => 'fa-search', 'label' => 'Recherches', 'value' => $totalSearches, 'color' => 'secondary']
            ];
            foreach ($statItems as $stat): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card hover-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-<?= $stat['color'] ?>-light rounded-circle me-3">
                                    <i class="fas <?= $stat['icon'] ?> text-<?= $stat['color'] ?>"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted fw-normal mb-1"><?= $stat['label'] ?></h6>
                                    <h3 class="mb-0 fw-bold"><?= formatNumber($stat['value']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Graphiques -->
    <section class="row g-4 mb-4">
        <!-- Graphique des rôles -->
        <div class="col-md-6 fade-in-delay-1">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users-cog me-2"></i> Répartition des rôles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="rolesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Graphique recherches par jour -->
        <div class="col-md-6 fade-in-delay-1">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i> Recherches par jour
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($searchData)): ?>
                        <div class="alert alert-info">
                            Aucune donnée de recherche disponible pour la période sélectionnée.
                        </div>
                    <?php else: ?>
                        <div class="chart-container">
                            <canvas id="searchChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Données détaillées -->
    <ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#searchTab">
                <i class="fas fa-search me-1"></i> Recherches
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#actionsTab">
                <i class="fas fa-history me-1"></i> Actions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#usersTab">
                <i class="fas fa-users me-1"></i> Utilisateurs
            </button>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Onglet recherches -->
        <div class="tab-pane fade show active" id="searchTab">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i> Détail des recherches
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Mot</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchLogs as $r): 
                                    $d = date('d/m/Y', strtotime($r['search_time']));
                                    $t = date('H:i', strtotime($r['search_time']));
                                ?>
                                <tr>
                                    <td><?= $d ?></td>
                                    <td><?= $t ?></td>
                                    <td><strong><?= htmlspecialchars($r['searched_word']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['user']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Onglet actions -->
        <div class="tab-pane fade" id="actionsTab">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i> Historique des actions
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actionLogs as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['user']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getActionBadgeClass($a['action_type']) ?>">
                                            <?= ucfirst(htmlspecialchars($a['action_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($a['description']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Onglet utilisateurs -->
        <div class="tab-pane fade" id="usersTab">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i> Statistiques utilisateurs
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Mots ajoutés</th>
                                    <th>Dernière connexion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Fusionner les données utilisateurs
                                $userStats = [];
                                foreach ($wordUserLogs as $w) {
                                    $userStats[$w['username']] = [
                                        'username' => $w['username'],
                                        'role' => $w['role'],
                                        'words' => $w['total'],
                                        'last_login' => null
                                    ];
                                }
                                foreach ($connLogs as $c) {
                                    if (isset($userStats[$c['username']])) {
                                        $userStats[$c['username']]['last_login'] = $c['last_login'];
                                    } else {
                                        $userStats[$c['username']] = [
                                            'username' => $c['username'],
                                            'role' => '?',
                                            'words' => 0,
                                            'last_login' => $c['last_login']
                                        ];
                                    }
                                }
                                foreach ($userStats as $u): 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'editor' ? 'success' : 'info') ?>">
                                            <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                        </span>
                                    </td>
                                    <td><?= formatNumber($u['words']) ?></td>
                                    <td><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

<!-- Styles pour la page -->
<style>
/* Variables */
:root {
    --primary-light: rgba(13, 110, 253, 0.15);
    --success-light: rgba(25, 135, 84, 0.15);
    --info-light: rgba(13, 202, 240, 0.15);
    --warning-light: rgba(255, 193, 7, 0.15);
    --danger-light: rgba(220, 53, 69, 0.15);
    --secondary-light: rgba(108, 117, 125, 0.15);
}

/* Animations */
.fade-in {
    animation: fadeIn 0.6s ease-in-out;
}
.fade-in-delay-1 {
    animation: fadeIn 0.6s ease-in-out 0.2s forwards;
    opacity: 0;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Cartes et conteneurs */
.hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}
.chart-container {
    position: relative;
    height: 250px;
}
.stat-card {
    border-left: 4px solid transparent;
}
.stat-card.hover-card:hover {
    border-left-width: 6px;
}

/* Icônes statistiques */
.stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.bg-primary-light { background-color: var(--primary-light); }
.bg-success-light { background-color: var(--success-light); }
.bg-info-light { background-color: var(--info-light); }
.bg-warning-light { background-color: var(--warning-light); }
.bg-danger-light { background-color: var(--danger-light); }
.bg-secondary-light { background-color: var(--secondary-light); }
</style>

<!-- Code JavaScript des graphiques (version minimale sans dépendances externes) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique répartition des rôles
    const rolesChart = document.getElementById('rolesChart');
    if (rolesChart) {
        new Chart(rolesChart, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($userRoles, 'role')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($userRoles, 'cnt')) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Graphique recherches par jour
    const searchChart = document.getElementById('searchChart');
    if (searchChart) {
        new Chart(searchChart, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function($s) { return date('d/m', strtotime($s['day'])); }, $searchData)) ?>,
                datasets: [{
                    label: 'Recherches',
                    data: <?= json_encode(array_column($searchData, 'cnt')) ?>,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});

// Version simplifiée de Chart.js incluse pour fonctionner hors ligne
class Chart {
    constructor(canvas, config) {
        this.canvas = canvas;
        this.config = config;
        this.ctx = canvas.getContext('2d');
        this.draw();
    }

    draw() {
        const ctx = this.ctx;
        const width = this.canvas.width;
        const height = this.canvas.height;
        
        // Effacer le canvas
        ctx.clearRect(0, 0, width, height);
        
        if (this.config.type === 'pie') {
            this.drawPieChart();
        } else if (this.config.type === 'bar') {
            this.drawBarChart();
        }
    }

    drawPieChart() {
        const ctx = this.ctx;
        const data = this.config.data.datasets[0].data;
        const labels = this.config.data.labels;
        const colors = this.config.data.datasets[0].backgroundColor;
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        const radius = Math.min(centerX, centerY) * 0.8;
        
        // Calculer le total pour les pourcentages
        const total = data.reduce((sum, value) => sum + value, 0);
        
        // Dessiner chaque tranche
        let startAngle = 0;
        for (let i = 0; i < data.length; i++) {
            const sliceAngle = 2 * Math.PI * data[i] / total;
            
            // Dessiner la tranche
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            ctx.closePath();
            
            // Colorer la tranche
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            
            // Bordure
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Texte (si l'espace est suffisant)
            if (sliceAngle > 0.2) {
                const middleAngle = startAngle + sliceAngle / 2;
                const textX = centerX + Math.cos(middleAngle) * radius * 0.7;
                const textY = centerY + Math.sin(middleAngle) * radius * 0.7;
                
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(labels[i], textX, textY);
            }
            
            startAngle += sliceAngle;
        }
        
        // Légende
        const legendY = this.canvas.height - 10;
        const legendWidth = 200;
        const legendX = (this.canvas.width - legendWidth) / 2;
        
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.font = '12px Arial';
        
        for (let i = 0; i < labels.length; i++) {
            const boxX = legendX + i * (legendWidth / labels.length);
            const boxY = legendY;
            
            // Carré de couleur
            ctx.fillStyle = colors[i % colors.length];
            ctx.fillRect(boxX, boxY, 10, 10);
            
            // Texte
            ctx.fillStyle = '#000000';
            ctx.fillText(labels[i], boxX + 15, boxY);
        }
    }

    drawBarChart() {
        const ctx = this.ctx;
        const data = this.config.data.datasets[0].data;
        const labels = this.config.data.labels;
        const color = this.config.data.datasets[0].backgroundColor;
        
        const width = this.canvas.width;
        const height = this.canvas.height;
        const padding = 40;
        const chartWidth = width - 2 * padding;
        const chartHeight = height - 2 * padding;
        
        // Trouver la valeur max pour l'échelle
        const maxValue = Math.max(...data, 1);
        const barWidth = chartWidth / data.length * 0.8;
        const barSpacing = chartWidth / data.length * 0.2;
        
        // Dessiner l'axe Y
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, height - padding);
        ctx.strokeStyle = '#888';
        ctx.stroke();
        
        // Dessiner l'axe X
        ctx.beginPath();
        ctx.moveTo(padding, height - padding);
        ctx.lineTo(width - padding, height - padding);
        ctx.strokeStyle = '#888';
        ctx.stroke();
        
        // Dessiner les barres
        for (let i = 0; i < data.length; i++) {
            const barHeight = (data[i] / maxValue) * chartHeight;
            const barX = padding + i * (barWidth + barSpacing);
            const barY = height - padding - barHeight;
            
            // Barre
            ctx.fillStyle = color;
            ctx.fillRect(barX, barY, barWidth, barHeight);
            
            // Valeur
            ctx.fillStyle = '#000';
            ctx.textAlign = 'center';
            ctx.fillText(data[i], barX + barWidth/2, barY - 5);
            
            // Label
            ctx.fillText(labels[i], barX + barWidth/2, height - padding + 15);
        }
        
        // Graduations Y
        const steps = 5;
        for (let i = 0; i <= steps; i++) {
            const y = height - padding - (i / steps) * chartHeight;
            const value = (i / steps) * maxValue;
            
            ctx.beginPath();
            ctx.moveTo(padding - 5, y);
            ctx.lineTo(padding, y);
            ctx.strokeStyle = '#888';
            ctx.stroke();
            
            ctx.fillStyle = '#888';
            ctx.textAlign = 'right';
            ctx.fillText(Math.round(value), padding - 8, y);
        }
    }
}
</script>

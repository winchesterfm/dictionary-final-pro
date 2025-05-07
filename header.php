<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Notifications
$notifCount = 0;
if (!empty($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $notifCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dictionnaire Multilingue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/solid.min.css">
    <link rel="stylesheet" href="assets/css/dark.css">

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/chart.min.js"></script>

    <style>
        .offcanvas {
            backdrop-filter: blur(8px);
            background-color: rgba(33, 37, 41, 0.95);
        }

        .offcanvas .nav-link {
            padding: 10px;
            transition: background 0.2s;
        }

        .offcanvas .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        .offcanvas .nav-link.text-danger:hover {
            background-color: rgba(255, 0, 0, 0.2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid px-4">

        <!-- ☰ Menu -->
        <?php if (!empty($_SESSION['user'])): ?>
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
                ☰
            </button>
        <?php endif; ?>

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/site_logo.png" alt="Logo" width="35" height="35" class="me-2">
            <strong>Dictionnaire</strong>
        </a>

        <!-- Navbar principal -->
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">🏠 Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="search.php">🔍 Recherche</a></li>
                <li class="nav-item"><a class="nav-link" href="organisation.php">🏢 Organisation</a></li>
            </ul>

            <!-- Droite -->
            <div class="d-flex align-items-center">
                <!-- 🌙 Dark mode -->
                <input type="checkbox" id="themeToggle" class="form-check-input me-2">
                <label for="themeToggle" class="text-light me-3">🌙</label>

                <!-- 🛎️ Notifications -->
                <?php if (!empty($_SESSION['user'])): ?>
                    <a href="notifications.php" class="btn btn-sm btn-outline-light position-relative me-2">
                        🛎️
                        <?php if ($notifCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $notifCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- 👤 Connexion / Profil -->
                <?php if (!empty($_SESSION['user'])): ?>
                    <a href="profile.php" class="btn btn-sm btn-outline-light me-2">👤 Profil</a>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-outline-light">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Marge -->
<div style="margin-top: 70px;"></div>

<!-- 🔽 Offcanvas Menu -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="offcanvasMenu">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">📁 Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link text-light" href="index.php">🏠 Accueil</a></li>
      <li class="nav-item"><a class="nav-link text-light" href="search.php">🔍 Recherche</a></li>
      <li class="nav-item"><a class="nav-link text-light" href="organisation.php">🏢 Organisation</a></li>
      <hr class="text-light">
      <?php if (!empty($_SESSION['user'])): ?>
          <?php
          $role = $_SESSION['user']['role'];
          if ($role === 'admin') {
              echo '
              <li><a class="nav-link text-light" href="dashboard.php">🏠 Dashboard</a></li>
              <li><a class="nav-link text-light" href="users.php">👥 Utilisateurs</a></li>
              <li><a class="nav-link text-light" href="add_word.php">➕ Ajouter un mot</a></li>
              <li><a class="nav-link text-light" href="stats.php">📊 Statistiques</a></li>
              <li><a class="nav-link text-light" href="notifications.php">🔔 Notifications</a></li>
              <li><a class="nav-link text-light" href="favorites.php">⭐ Favoris</a></li>';
          } elseif ($role === 'editor') {
              echo '
              <li><a class="nav-link text-light" href="dashboard_editor.php">🏠 Dashboard</a></li>
              <li><a class="nav-link text-light" href="add_word.php">➕ Ajouter un mot</a></li>
              <li><a class="nav-link text-light" href="favorites.php">⭐ Favoris</a></li>';
          } else {
              echo '<li><a class="nav-link text-light" href="favorites.php">⭐ Favoris</a></li>';
          }
          ?>
          <hr class="text-light">
          <li><a class="nav-link text-light" href="profile.php">👤 Mon profil</a></li>
          <li><a class="nav-link text-danger" href="logout.php">🚪 Déconnexion</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- Script Dark Mode -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("themeToggle");
    const isDark = localStorage.getItem("theme") === "dark";
    if (isDark) {
        document.body.classList.add("dark-mode");
        toggle.checked = true;
    }

    toggle.addEventListener("change", function () {
        document.body.classList.toggle("dark-mode", this.checked);
        localStorage.setItem("theme", this.checked ? "dark" : "light");
    });
});
</script>

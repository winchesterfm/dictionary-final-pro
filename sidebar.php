<?php
// sidebar.php
?>
<div id="sidebar" class="sidebar-expanded bg-dark text-light position-fixed h-100 p-3 shadow-lg glass" style="width: 250px; top: 0; left: 0; overflow-y: auto; transition: all 0.3s ease-in-out; z-index: 1040;">
    <div class="d-flex flex-column align-items-start">

        <!-- Bouton Toggle Collapse -->
        <button id="collapseSidebarBtn" class="btn btn-sm btn-outline-light mb-4 w-100 text-start">
            <i class="fas fa-angle-left me-2"></i><span class="collapse-label">Réduire</span>
        </button>

        <!-- Logo -->
        <a href="index.php" class="mb-4 text-decoration-none text-light fs-5 w-100 text-start">
            <i class="fas fa-book-open me-2"></i><span class="collapse-label">Dictionnaire</span>
        </a>

        <!-- Menu -->
        <ul class="nav flex-column w-100">
            <?php if (!empty($_SESSION['user'])): ?>
                <?php
                    $role = $_SESSION['user']['role'];
                    $items = [
                        'admin' => [
                            ['dashboard.php', 'fa-home', 'Dashboard'],
                            ['users.php', 'fa-users', 'Utilisateurs'],
                            ['add_word.php', 'fa-plus', 'Ajouter un mot'],
                            ['stats.php', 'fa-chart-bar', 'Statistiques'],
                            ['notifications.php', 'fa-bell', 'Notifications'],
                            ['favorites.php', 'fa-star', 'Favoris'],
                        ],
                        'editor' => [
                            ['dashboard_editor.php', 'fa-home', 'Dashboard'],
                            ['add_word.php', 'fa-plus', 'Ajouter un mot'],
                            ['favorites.php', 'fa-star', 'Favoris'],
                        ],
                        'viewer' => [
                            ['index.php', 'fa-home', 'Accueil'],
                            ['favorites.php', 'fa-star', 'Favoris'],
                        ]
                    ];
                    foreach ($items[$role] as [$link, $icon, $label]) {
                        echo "<li class='nav-item'>
                            <a class='nav-link text-light' href='$link'>
                                <i class='fas $icon me-2'></i><span class='collapse-label'>$label</span>
                            </a>
                        </li>";
                    }
                ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Styles supplémentaires -->
<style>
#sidebar {
    backdrop-filter: blur(10px);
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.3);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

/* Mode réduit */
#sidebar.collapsed {
    width: 70px;
}

#sidebar.collapsed .collapse-label {
    display: none;
}

body.sidebar-collapsed main {
    margin-left: 70px !important;
}

@media (min-width: 992px) {
    main {
        margin-left: 250px;
        transition: margin-left 0.3s ease-in-out;
    }
}
</style>

<!-- Script pour toggle sidebar -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById('collapseSidebarBtn');
    const sidebar = document.getElementById('sidebar');

    if (btn) {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');

            // Changer l’icône du bouton
            const icon = btn.querySelector('i');
            icon.classList.toggle('fa-angle-left');
            icon.classList.toggle('fa-angle-right');

            const label = btn.querySelector('.collapse-label');
            if (label) label.textContent = sidebar.classList.contains('collapsed') ? '' : 'Réduire';
        });
    }
});
</script>

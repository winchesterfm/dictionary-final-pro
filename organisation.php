<?php
require 'config.php';
require 'header.php';
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">🏢 Organisation du dictionnaire</h2>

    <div class="card shadow p-4 mb-4">
        <h4>📌 Objectif du projet</h4>
        <p>
            Ce dictionnaire multilingue permet de rechercher, ajouter et organiser des mots en trois langues : français, anglais et espagnol.
            Il est conçu pour une utilisation collaborative, avec différents niveaux d’accès.
        </p>
    </div>

    <div class="card shadow p-4 mb-4">
        <h4>👥 Rôles des utilisateurs</h4>
        <ul>
            <li><strong>Admin :</strong> Gère les utilisateurs, notifications, statistiques, et tout le contenu.</li>
            <li><strong>Editor :</strong> Peut ajouter de nouveaux mots avec leurs définitions.</li>
            <li><strong>Viewer :</strong> Peut consulter les mots, faire des recherches et enregistrer des favoris.</li>
        </ul>
    </div>

    <div class="card shadow p-4 mb-4">
        <h4>🔐 Accès & Sécurité</h4>
        <p>
            Chaque utilisateur dispose d’un mot de passe personnel. Les administrateurs peuvent réinitialiser les mots de passe en cas d’oubli.
            Un système de notifications informe les utilisateurs de toute mise à jour.
        </p>
    </div>

    <div class="card shadow p-4 mb-4">
        <h4>📊 Statistiques & Suivi</h4>
        <p>
            Les recherches, ajouts de mots et favoris sont enregistrés pour assurer un suivi et améliorer l'expérience utilisateur.
        </p>
    </div>
</main>

<?php require 'footer.php'; ?>

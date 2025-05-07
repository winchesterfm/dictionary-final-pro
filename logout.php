<?php
require 'config.php';

// Log avant de détruire la session
if (!empty($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];

    log_action($pdo, $userId, 'deconnexion', "Déconnexion de l'utilisateur \"$username\"");
}

// Déconnexion
session_unset();
session_destroy();

// Redirection
header("Location: login.php");
exit;

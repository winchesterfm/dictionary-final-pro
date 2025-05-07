<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    echo "⚠️ Vous devez être connecté pour tester les logs.";
    exit;
}

// Ne pas redéclarer log_action ici, elle est déjà dans config.php

// ✅ Enregistrement d'un log de test
log_action($pdo, $_SESSION['user']['id'], 'test_action', 'Test manuel via test_logs_2.php');

echo "✅ Log ajouté avec succès pour l'utilisateur {$_SESSION['user']['username']}";

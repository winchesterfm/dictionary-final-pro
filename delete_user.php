<?php
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$targetId = intval($_GET['id']);
$currentId = $_SESSION['user']['id'];

// Récupérer l’utilisateur ciblé
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "❌ Utilisateur introuvable.";
    header("Location: users.php");
    exit;
}

// ⚠️ Ne pas supprimer l’admin connecté
if ($targetId === $currentId) {
    $_SESSION['error'] = "⚠️ Vous ne pouvez pas vous supprimer vous-même.";
    header("Location: users.php");
    exit;
}

// ⚠️ Ne pas supprimer un autre admin
if ($user['role'] === 'admin') {
    $_SESSION['error'] = "⚠️ Vous ne pouvez pas supprimer un autre administrateur.";
    header("Location: users.php");
    exit;
}

// Supprimer l'utilisateur
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$targetId]);

// Log de suppression
$adminId = $_SESSION['user']['id'];
log_action($pdo, $adminId, 'suppression_utilisateur', "Suppression de l'utilisateur \"{$user['username']}\" (rôle : {$user['role']})");

$_SESSION['success'] = "✅ Utilisateur supprimé avec succès.";
header("Location: users.php");
exit;

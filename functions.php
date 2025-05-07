<?php
// functions.php

/**
 * Gère un cache JSON de courte durée.
 * @return mixed soit les données mises en cache, soit null si à rafraîchir.
 */
function cacheGet(string $path, int $ttl = 86400) {
    if (file_exists($path) && (time() - filemtime($path) < $ttl)) {
        return json_decode(file_get_contents($path), true);
    }
    return null;
}
function cacheSet(string $path, $data): void {
    file_put_contents($path, json_encode($data), LOCK_EX);
}

/**
 * Récupère le mot du jour + définitions.
 */
function getWordOfTheDay(PDO $pdo): array {
    $today = date('Y-m-d');
    $icons = ['📘','📙','📕','📗','📓','📒','📚'];
    $icon  = $icons[crc32($today) % count($icons)];
    $count = (int)$pdo->query("SELECT COUNT(*) FROM words")->fetchColumn();
    if ($count === 0) {
        return [null, [], $icon];
    }
    $idx = crc32($today) % $count;
    $stmt = $pdo->prepare("SELECT id, word FROM words ORDER BY id LIMIT 1 OFFSET :i");
    $stmt->bindValue(':i', $idx, PDO::PARAM_INT);
    $stmt->execute();
    $w = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$w) {
        return [null, [], $icon];
    }
    $d = $pdo->prepare("SELECT lang_code, definition FROM definitions WHERE word_id = ?");
    $d->execute([$w['id']]);
    $defs = $d->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
    return [$w, $defs, $icon];
}

/**
 * Récupère les N mots les plus recherchés.
 */
function getPopularWords(PDO $pdo, int $limit = 6): array {
    $p = $pdo->prepare("
        SELECT searched_word AS word, COUNT(*) AS cnt
        FROM search_logs
        GROUP BY searched_word
        ORDER BY cnt DESC
        LIMIT :l
    ");
    $p->bindValue(':l', $limit, PDO::PARAM_INT);
    $p->execute();
    return $p->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les N mots récents avec auteur (si connu).
 */
function getRecentWords(PDO $pdo, int $limit = 6): array {
    $r = $pdo->prepare("
        SELECT w.id, w.word, u.username
        FROM words w
        LEFT JOIN users u ON u.id = w.created_by
        ORDER BY w.created_at DESC
        LIMIT :l
    ");
    $r->bindValue(':l', $limit, PDO::PARAM_INT);
    $r->execute();
    return $r->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retourne true si ce mot est en favori pour cet utilisateur.
 */
function isFavorite(PDO $pdo, int $wordId, int $userId): bool {
    $f = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE word_id=? AND user_id=?");
    $f->execute([$wordId, $userId]);
    return (bool)$f->fetchColumn();
}

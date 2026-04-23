<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!hash_equals(csrfToken(), $csrf)) {
    $_SESSION['admin_error'] = 'Token CSRF invalide.';
    header('Location: ' . APP_URL . '/admin/cards.php');
    exit;
}

$card = dbQueryOne('SELECT * FROM cards WHERE id = ?', [$id]);
if (!$card) {
    $_SESSION['admin_error'] = 'Carte introuvable.';
    header('Location: ' . APP_URL . '/admin/cards.php');
    exit;
}

if (!isOwner() && (string)($card['author_id'] ?? '') !== (string)$_SESSION['user_id']) {
    $_SESSION['admin_error'] = 'Vous ne pouvez supprimer que vos propres cartes.';
    header('Location: ' . APP_URL . '/admin/cards.php');
    exit;
}

// Supprimer l'image
if ($card['image_file'] && file_exists(CARDS_DIR . $card['image_file'])) {
    unlink(CARDS_DIR . $card['image_file']);
}

dbExecute('DELETE FROM cards WHERE id = ?', [$id]);
$_SESSION['admin_success'] = 'Carte « ' . $card['name'] . ' » supprimée.';
header('Location: ' . APP_URL . '/admin/cards.php');
exit;

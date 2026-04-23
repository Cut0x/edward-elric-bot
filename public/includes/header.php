<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Accueil');

require_once __DIR__ . '/bot_api.php';

$botAvatar = getBotAvatarUrl(64);
$botName   = getBotName();

function navActive(string $page): string {
    return basename($_SERVER['PHP_SELF']) === $page ? ' active' : '';
}
function adminActive(): string {
    return str_contains($_SERVER['PHP_SELF'], '/admin/') ? ' active' : '';
}

function levelBadgeClass(int $level): string {
    if ($level >= 51) return 'lv-legendaire';
    if ($level >= 36) return 'lv-grand';
    if ($level >= 21) return 'lv-elite';
    if ($level >= 11) return 'lv-etat';
    if ($level >= 6)  return 'lv-apprenti';
    return 'lv-novice';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1f22">
    <meta name="description" content="<?= h($botName) ?> — Collectionnez des cartes Fullmetal Alchemist sur Discord !">
    <title><?= h(PAGE_TITLE) ?> — <?= h($botName) ?></title>

    <!-- Favicon dynamique depuis Discord -->
    <?php if ($botAvatar && str_starts_with($botAvatar, 'https://cdn.discordapp.com')): ?>
        <link rel="icon" type="image/png" href="<?= h($botAvatar) ?>">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cinzel:wght@700;900&family=JetBrains+Mono:wght@400;600&display=swap">
    <!-- CSS principal -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= APP_URL ?>/index.php">
            <img class="navbar-bot-avatar"
                 src="<?= h($botAvatar) ?>"
                 alt="<?= h($botName) ?>"
                 onerror="this.src='<?= APP_URL ?>/assets/imgs/banner.gif'">
            <span class="navbar-bot-name"><?= h($botName) ?></span>
        </a>

        <div class="navbar-sep"></div>

        <!-- Links -->
        <div class="navbar-links" id="navbar-links">
            <a href="<?= APP_URL ?>/index.php" class="nav-link<?= navActive('index.php') ?>">
                <i class="bi bi-house-fill"></i> Accueil
            </a>
            <a href="<?= APP_URL ?>/cards.php" class="nav-link<?= navActive('cards.php') ?>">
                <i class="bi bi-collection-fill"></i> Cartes
            </a>
            <a href="<?= APP_URL ?>/leaderboard.php" class="nav-link<?= navActive('leaderboard.php') ?>">
                <i class="bi bi-trophy-fill"></i> Classement
            </a>
            <a href="<?= APP_URL ?>/users.php" class="nav-link<?= navActive('users.php') ?>">
                <i class="bi bi-people-fill"></i> Joueurs
            </a>
            <a href="<?= APP_URL ?>/community.php" class="nav-link<?= navActive('community.php') ?>">
                <i class="bi bi-chat-square-heart-fill"></i> Forum
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/roll.php" class="nav-link<?= navActive('roll.php') ?>">
                    <i class="bi bi-dice-5-fill"></i> Roll
                </a>
                <a href="<?= APP_URL ?>/collection.php" class="nav-link<?= navActive('collection.php') ?>">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Collection
                </a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a href="<?= APP_URL ?>/admin/index.php" class="nav-link<?= adminActive() ?>" style="color:#e3a321;">
                    <i class="bi bi-shield-lock-fill"></i> Admin
                </a>
            <?php endif; ?>
        </div>

        <!-- Right side -->
        <div class="navbar-right">
            <?php if (isLoggedIn()):
                $userXp    = (int)(dbQueryOne('SELECT xp FROM users WHERE id = ?', [$_SESSION['user_id']])['xp'] ?? 0);
                $userLevel = xpToLevel($userXp);
                $lvClass   = levelBadgeClass($userLevel);
            ?>
                <a href="<?= APP_URL ?>/collection.php" class="user-pill" title="Ma collection">
                    <img class="user-pill-avatar"
                         src="<?= h(getAvatarUrl($_SESSION['user_id'], $_SESSION['avatar'], 64)) ?>"
                         alt=""
                         onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                    <span class="user-pill-name"><?= h($_SESSION['global_name'] ?? $_SESSION['username']) ?></span>
                    <span class="level-badge <?= $lvClass ?>">Nv.<?= $userLevel ?></span>
                </a>
                <a href="<?= APP_URL ?>/logout.php" class="btn-outline-sm" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn-discord">
                    <i class="bi bi-discord"></i> Connexion
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile toggle -->
        <button class="navbar-toggle" id="navbar-toggle" aria-expanded="false" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<script>
// Inline pour éviter le flash au chargement
document.addEventListener('DOMContentLoaded', function() {
    var t = document.getElementById('navbar-toggle');
    var l = document.getElementById('navbar-links');
    if (t && l) {
        t.onclick = function() { l.classList.toggle('open'); };
    }
});
</script>

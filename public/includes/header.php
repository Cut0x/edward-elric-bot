<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Accueil');

require_once __DIR__ . '/bot_api.php';

if (!defined('APP_URL_REWRITE_BUFFER')) {
    define('APP_URL_REWRITE_BUFFER', true);
    ob_start('rewriteAppUrls');
}

$botAvatar = getBotAvatarUrl(64);
$botName   = getBotName();

function navActive(string $page): string {
    return basename($_SERVER['PHP_SELF']) === $page ? ' active' : '';
}
function adminActive(): string {
    return str_contains($_SERVER['PHP_SELF'], '/admin/') ? ' active' : '';
}
function navSection(array $pages): string {
    foreach ($pages as $page) {
        if (basename($_SERVER['PHP_SELF']) === $page) return ' active';
    }
    return '';
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

    <?php if ($botAvatar && str_starts_with($botAvatar, 'https://cdn.discordapp.com')): ?>
        <link rel="icon" type="image/png" href="<?= h($botAvatar) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@700;900&family=JetBrains+Mono:wght@400;600&display=swap">
    <link rel="stylesheet" href="<?= appUrl('/assets/css/style.css') ?>">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">

        <!-- Brand -->
        <a class="navbar-brand" href="<?= appUrl('/') ?>">
            <img class="navbar-bot-avatar"
                 src="<?= h($botAvatar) ?>"
                 alt="<?= h($botName) ?>"
                 onerror="this.src='<?= appUrl('/assets/imgs/banner.gif') ?>'">
            <span class="navbar-bot-name"><?= h($botName) ?></span>
        </a>

        <div class="navbar-sep"></div>

        <!-- Nav links -->
        <div class="navbar-links" id="navbar-links">

            <a href="<?= appUrl('/') ?>" class="nav-link<?= navActive('index.php') ?>">
                <i class="bi bi-house-fill"></i> Accueil
            </a>

            <!-- Explorer dropdown -->
            <div class="navbar-group" id="ng-explorer">
                <button class="nav-link has-dropdown<?= navSection(['cards.php','card.php','leaderboard.php','users.php','user.php','creators.php']) ?>"
                        onclick="toggleMobileGroup('ng-explorer')" type="button">
                    <i class="bi bi-compass-fill"></i>
                    Explorer
                    <i class="bi bi-chevron-down nav-chevron"></i>
                </button>
                <div class="navbar-dropdown">
                    <div class="dropdown-section">
                        <div class="dropdown-label">Cartes &amp; Collection</div>
                        <a class="dropdown-item" href="<?= appUrl('/cards') ?>">
                            <i class="bi bi-collection-fill"></i>
                            <span>Toutes les cartes
                                <span class="di-sub">Parcourir le catalogue</span>
                            </span>
                        </a>
                        <a class="dropdown-item" href="<?= appUrl('/creators') ?>">
                            <i class="bi bi-brush-fill"></i>
                            <span>Artistes &amp; Créateurs
                                <span class="di-sub">Les auteurs des cartes</span>
                            </span>
                        </a>
                    </div>
                    <div class="dropdown-section">
                        <div class="dropdown-label">Classements</div>
                        <a class="dropdown-item" href="<?= appUrl('/leaderboard') ?>">
                            <i class="bi bi-trophy-fill"></i>
                            <span>Classement
                                <span class="di-sub">Top collectionneurs</span>
                            </span>
                        </a>
                        <a class="dropdown-item" href="<?= appUrl('/users') ?>">
                            <i class="bi bi-people-fill"></i>
                            <span>Joueurs
                                <span class="di-sub">Voir tous les profils</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Forum -->
            <a href="<?= appUrl('/community') ?>" class="nav-link<?= navSection(['community.php','proposal.php']) ?>">
                <i class="bi bi-chat-square-heart-fill"></i> Forum
            </a>

            <?php if (isLoggedIn()):
                $__rollsLeft = (int)(dbQueryOne('SELECT rolls_remaining FROM users WHERE id = ?', [$_SESSION['user_id']])['rolls_remaining'] ?? 0);
            ?>
            <!-- Jouer dropdown -->
            <div class="navbar-group" id="ng-jouer">
                <button class="nav-link has-dropdown<?= navSection(['roll.php','collection.php']) ?>"
                        onclick="toggleMobileGroup('ng-jouer')" type="button">
                    <i class="bi bi-dice-5-fill"></i>
                    Jouer
                    <i class="bi bi-chevron-down nav-chevron"></i>
                </button>
                <div class="navbar-dropdown">
                    <div class="dropdown-section">
                        <a class="dropdown-item" href="<?= appUrl('/roll') ?>">
                            <i class="bi bi-dice-5-fill"></i>
                            <span>Roll du jour
                                <span class="di-sub">Obtenir une nouvelle carte</span>
                            </span>
                            <?php if ($__rollsLeft > 0): ?>
                                <span class="di-badge"><?= $__rollsLeft ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="dropdown-item" href="<?= appUrl('/collection') ?>">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            Ma Collection
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <a href="<?= appUrl('/admin/index') ?>" class="nav-link<?= adminActive() ?>" style="color:var(--gold-4);">
                <i class="bi bi-shield-lock-fill"></i> Admin
            </a>
            <?php endif; ?>

        </div>

        <!-- Right side -->
        <div class="navbar-right">
            <?php if (isLoggedIn()):
                $__xp      = (int)(dbQueryOne('SELECT xp FROM users WHERE id = ?', [$_SESSION['user_id']])['xp'] ?? 0);
                $__level   = xpToLevel($__xp);
                $__lvClass = levelBadgeClass($__level);
            ?>
                <div class="navbar-user navbar-group">
                    <div class="user-pill" tabindex="0" aria-haspopup="true">
                        <img class="user-pill-avatar"
                             src="<?= h(getAvatarUrl($_SESSION['user_id'], $_SESSION['avatar'], 64)) ?>"
                             alt=""
                             onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                        <span class="user-pill-name"><?= h($_SESSION['global_name'] ?? $_SESSION['username']) ?></span>
                        <span class="level-badge <?= $__lvClass ?>">Nv.<?= $__level ?></span>
                    </div>
                    <div class="navbar-dropdown">
                        <div class="dropdown-section">
                            <a class="dropdown-item" href="<?= appUrl('/user.php?id=' . urlencode($_SESSION['user_id'])) ?>">
                                <i class="bi bi-person-fill"></i>
                                <span>Mon Profil
                                    <span class="di-sub">@<?= h($_SESSION['username']) ?></span>
                                </span>
                            </a>
                            <a class="dropdown-item" href="<?= appUrl('/collection') ?>">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                                Ma Collection
                            </a>
                            <a class="dropdown-item" href="<?= appUrl('/roll') ?>">
                                <i class="bi bi-dice-5-fill"></i>
                                Roll du jour
                            </a>
                        </div>
                        <div class="dropdown-section">
                            <a class="dropdown-item" href="<?= appUrl('/logout') ?>" style="color:var(--red);">
                                <i class="bi bi-box-arrow-right" style="color:var(--red);"></i>
                                Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= appUrl('/login') ?>" class="btn-discord">
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
(function() {
    var toggle = document.getElementById('navbar-toggle');
    var links  = document.getElementById('navbar-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function() {
        var open = links.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('i').className = open ? 'bi bi-x-lg' : 'bi bi-list';
    });

    document.addEventListener('click', function(e) {
        if (!toggle.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('open');
            toggle.querySelector('i').className = 'bi bi-list';
        }
    });
})();

function toggleMobileGroup(id) {
    if (window.innerWidth > 768) return;
    var el = document.getElementById(id);
    if (el) el.classList.toggle('mobile-open');
}
</script>

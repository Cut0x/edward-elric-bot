<?php
define('PAGE_TITLE', 'Connexion');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

if (!DISCORD_CLIENT_ID || !DISCORD_CLIENT_SECRET) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:5rem 0;text-align:center;">
        <div class="alert-alch alert-error" style="max-width:500px;margin:0 auto;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Le bot n\'est pas encore configuré. Remplissez le fichier <code>.env</code>.
        </div>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

header('Location: ' . getDiscordAuthUrl());
exit;

<?php
define('PAGE_TITLE', 'Connexion...');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = null;

if (isset($_GET['error'])) {
    $error = 'Connexion annulée par Discord.';
} elseif (!isset($_GET['code'], $_GET['state'])) {
    $error = 'Paramètres manquants.';
} elseif (!hash_equals($_SESSION['oauth_state'] ?? '', $_GET['state'])) {
    $error = 'État OAuth invalide. Réessayez.';
} else {
    $tokenData = exchangeCodeForToken($_GET['code']);
    if (!$tokenData) {
        $error = 'Impossible d\'obtenir le token Discord.';
    } else {
        $discordUser = getDiscordUser($tokenData['access_token']);
        if (!$discordUser) {
            $error = 'Impossible de récupérer votre profil Discord.';
        } else {
            unset($_SESSION['oauth_state']);
            try {
                loginUser($discordUser);
                header('Location: ' . APP_URL . '/index.php?message=welcome');
                exit;
            } catch (Exception $e) {
                $error = 'Erreur lors de la connexion. Réessayez.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding:6rem 0;text-align:center;">
    <div style="max-width:450px;margin:0 auto;">
        <?php if ($error): ?>
            <i class="bi bi-x-circle-fill" style="font-size:3rem;color:var(--red-light);"></i>
            <h2 style="margin:1rem 0;font-family:'Cinzel',serif;color:var(--text-bright);">Erreur de connexion</h2>
            <p style="color:var(--text-muted);margin-bottom:2rem;"><?= h($error) ?></p>
            <a href="<?= APP_URL ?>/login.php" class="btn-alch btn-alch-gold">
                <i class="bi bi-arrow-left"></i> Réessayer
            </a>
        <?php else: ?>
            <div style="animation:brandSpin 1s linear infinite;width:60px;height:60px;border-radius:50%;border:3px solid var(--gold);border-top-color:transparent;margin:0 auto 1.5rem;"></div>
            <h2 style="font-family:'Cinzel',serif;color:var(--text-bright);">Connexion en cours...</h2>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

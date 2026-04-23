<?php
$botAvatar = getBotAvatarUrl(64);
$botName   = getBotName();
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div>
                <div class="footer-brand">
                    <img class="footer-bot-avatar"
                         src="<?= h($botAvatar) ?>"
                         alt="<?= h($botName) ?>"
                         onerror="this.style.display='none'">
                    <span class="footer-brand-name"><?= h($botName) ?></span>
                </div>
                <p class="footer-desc">
                    Un jeu de collection de cartes gratuit dans l'univers de Fullmetal Alchemist.
                    Rollez chaque jour, montez en niveau et complétez votre collection !
                </p>
                <div class="footer-commands" style="margin-top:16px;">
                    <div class="footer-commands-label">Commandes Discord</div>
                    <div class="footer-commands-list">
                        <?php foreach (['/roll', '/reroll', '/collection', '/profil', '/top', '/carte', '/aide'] as $cmd): ?>
                            <code class="cmd"><?= $cmd ?></code>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div>
                <div class="footer-heading">Navigation</div>
                <ul class="footer-links">
                    <li><a href="<?= appUrl('/') ?>"><i class="bi bi-house"></i> Accueil</a></li>
                    <li><a href="<?= appUrl('/cards') ?>"><i class="bi bi-collection"></i> Toutes les cartes</a></li>
                    <li><a href="<?= appUrl('/creators') ?>"><i class="bi bi-brush"></i> Artistes &amp; Créateurs</a></li>
                    <li><a href="<?= appUrl('/leaderboard') ?>"><i class="bi bi-trophy"></i> Classement</a></li>
                    <li><a href="<?= appUrl('/users') ?>"><i class="bi bi-people"></i> Joueurs</a></li>
                    <li><a href="<?= appUrl('/community') ?>"><i class="bi bi-chat-square-heart"></i> Forum communautaire</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= appUrl('/collection') ?>"><i class="bi bi-grid-3x3-gap"></i> Ma collection</a></li>
                        <li><a href="<?= appUrl('/roll') ?>"><i class="bi bi-dice-5"></i> Roll du jour</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Bot -->
            <div>
                <div class="footer-heading">Bot Discord</div>
                <ul class="footer-links">
                    <li><a href="#"><i class="bi bi-discord"></i> Inviter le bot</a></li>
                    <li><a href="#"><i class="bi bi-question-circle"></i> Aide (/aide)</a></li>
                    <li><a href="#"><i class="bi bi-github"></i> Code source</a></li>
                </ul>
            </div>

            <!-- Légal -->
            <div>
                <div class="footer-heading">Légal</div>
                <ul class="footer-links">
                    <li><a href="<?= appUrl('/terms') ?>"><i class="bi bi-file-earmark-text"></i> Conditions d'utilisation</a></li>
                    <li><a href="<?= appUrl('/privacy') ?>"><i class="bi bi-shield-lock"></i> Confidentialité</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?= date('Y') ?> <?= h($botName) ?> — Projet non-officiel, non affilié à Arakawa Hiromu.
            </p>
            <p class="footer-quote">« Rien ne se crée sans sacrifice. »</p>
        </div>
    </div>
</footer>

<div class="toast-container"></div>
<script src="<?= appUrl('/assets/js/main.js') ?>"></script>
</body>
</html>
<?php if (defined('APP_URL_REWRITE_BUFFER') && ob_get_level() > 0) ob_end_flush(); ?>

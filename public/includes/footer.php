<?php
$botAvatar = getBotAvatarUrl(64);
$botName   = getBotName();
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
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
            </div>

            <div>
                <div class="footer-heading">Navigation</div>
                <ul class="footer-links">
                    <li><a href="<?= APP_URL ?>/index.php"><i class="bi bi-house"></i> Accueil</a></li>
                    <li><a href="<?= APP_URL ?>/cards.php"><i class="bi bi-collection"></i> Toutes les cartes</a></li>
                    <li><a href="<?= APP_URL ?>/leaderboard.php"><i class="bi bi-trophy"></i> Classement</a></li>
                    <li><a href="<?= APP_URL ?>/users.php"><i class="bi bi-people"></i> Joueurs</a></li>
                    <li><a href="<?= APP_URL ?>/community.php"><i class="bi bi-chat-square-heart"></i> Forum communautaire</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= APP_URL ?>/collection.php"><i class="bi bi-grid-3x3-gap"></i> Ma collection</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div>
                <div class="footer-heading">Bot Discord</div>
                <ul class="footer-links">
                    <li><a href="#"><i class="bi bi-discord"></i> Inviter le bot</a></li>
                    <li><a href="#"><i class="bi bi-question-circle"></i> Aide (/aide)</a></li>
                    <li><a href="#"><i class="bi bi-github"></i> Code source</a></li>
                </ul>
                <div style="margin-top:14px;padding:10px 12px;background:var(--bg-500);border-radius:var(--radius-md);border:1px solid var(--b-1);">
                    <div style="font-size:11px;color:var(--text-5);margin-bottom:6px;">COMMANDES</div>
                    <?php foreach (['/roll', '/reroll', '/collection', '/profil', '/top'] as $cmd): ?>
                        <span style="display:inline-block;margin:2px 2px;"><code class="cmd"><?= $cmd ?></code></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <div class="footer-heading">Légal</div>
                <ul class="footer-links">
                    <li><a href="<?= APP_URL ?>/terms.php"><i class="bi bi-file-earmark-text"></i> Conditions d'utilisation</a></li>
                    <li><a href="<?= APP_URL ?>/privacy.php"><i class="bi bi-shield-lock"></i> Politique de confidentialité</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?= date('Y') ?> <?= h($botName) ?> — Projet non-officiel, non affilié à Arakawa Hiromu.
            </p>
            <p class="footer-quote">
                « Rien ne se crée sans sacrifice. »
            </p>
        </div>
    </div>
</footer>

<div class="toast-container"></div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>

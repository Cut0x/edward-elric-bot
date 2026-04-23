<?php
define('PAGE_TITLE', 'Accueil');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$stats  = getStats();
$recent = getRecentCards(6);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══ HERO ═══════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-banner"></div>
    <div class="hero-body">
        <div class="hero-bot-row">
            <div style="position:relative;flex-shrink:0;">
                <img class="hero-bot-avatar"
                     src="<?= h(getBotAvatarUrl(160)) ?>"
                     alt="<?= h(getBotName()) ?>"
                     onerror="this.src='<?= APP_URL ?>/assets/imgs/banner.gif'">
                <span class="hero-online-dot"></span>
            </div>
            <div>
                <h1 class="hero-title"><?= h(getBotName()) ?></h1>
                <p class="hero-tag">Bot Discord • Collection de cartes • Fullmetal Alchemist</p>
            </div>
        </div>

        <p class="hero-desc">
            Obtenez des cartes de l'univers Fullmetal Alchemist chaque jour sur Discord.
            Montez en niveau, complétez votre Pokédex d'alchimistes et devenez le meilleur collectionneur !
        </p>

        <div class="hero-stats-row">
            <div class="hero-stat">
                <span class="hero-stat-num"><?= number_format($stats['total_cards']) ?></span>
                <span class="hero-stat-label">Cartes</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-num"><?= number_format($stats['total_users']) ?></span>
                <span class="hero-stat-label">Joueurs</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-num"><?= number_format($stats['total_rolls']) ?></span>
                <span class="hero-stat-label">Rolls effectués</span>
            </div>
        </div>

        <div class="hero-cta-row">
            <?php if (isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/collection.php" class="btn-primary">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Ma Collection
                </a>
                <a href="<?= APP_URL ?>/cards.php" class="btn-secondary">
                    <i class="bi bi-collection-fill"></i> Voir les cartes
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn-primary">
                    <i class="bi bi-discord"></i> Se connecter avec Discord
                </a>
                <a href="<?= APP_URL ?>/cards.php" class="btn-secondary">
                    <i class="bi bi-eye-fill"></i> Explorer les cartes
                </a>
                <span style="font-size:12px;color:var(--text-5);align-self:center;">Gratuit · Français · Aucune inscription</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ CONTENT ════════════════════════════════════════════════ -->
<div class="container page-content">

    <!-- Stats row -->
    <div class="stats-grid mb-24">
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-collection-fill"></i></div>
            <div class="stat-block-num"><?= $stats['total_cards'] ?></div>
            <div class="stat-block-label">Cartes disponibles</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-block-num"><?= $stats['total_users'] ?></div>
            <div class="stat-block-label">Collectionneurs</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon">
                <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:18px;height:18px;" alt="XP">
            </div>
            <div class="stat-block-num"><?= number_format($stats['total_rolls']) ?></div>
            <div class="stat-block-label">Rolls effectués</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon">
                <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:18px;height:18px;" alt="Star">
            </div>
            <div class="stat-block-num">5</div>
            <div class="stat-block-label">Niveaux de rareté</div>
        </div>
    </div>

    <!-- Comment jouer -->
    <div class="page-section">
        <div class="section-header">
            <div>
                <div class="section-title"><i class="bi bi-controller"></i> Comment jouer ?</div>
                <div class="section-meta">Quatre étapes pour devenir un grand collectionneur</div>
            </div>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <div class="step-icon"><i class="bi bi-discord"></i></div>
                <div class="step-title">Rejoindre</div>
                <p class="step-text">Invitez le bot sur votre serveur ou utilisez-le là où il est déjà présent. Votre compte est lié à votre ID Discord, il fonctionne partout.</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <div class="step-icon"><i class="bi bi-dice-5-fill"></i></div>
                <div class="step-title">Roller</div>
                <p class="step-text">Utilisez <code class="cmd">/roll</code> jusqu'à <?= MAX_DAILY_ROLLS ?> fois par jour pour obtenir des cartes de différentes raretés. Les rolls se rechargent chaque jour à minuit.</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <div class="step-icon">
                    <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:20px;height:20px;" alt="XP">
                </div>
                <div class="step-title">Gagner de l'XP</div>
                <p class="step-text">Chaque roll rapporte de l'XP. Les cartes rares en donnent plus. Montez de niveau pour débloquer des titres spéciaux et apparaître dans le classement.</p>
            </div>
            <div class="step-card">
                <div class="step-num">4</div>
                <div class="step-icon">
                    <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:20px;height:20px;" alt="Star">
                </div>
                <div class="step-title">Tout collectionner</div>
                <p class="step-text">Complétez votre collection en obtenant toutes les cartes, des communes aux légendaires. Utilisez <code class="cmd">/collection</code> pour voir votre progression.</p>
            </div>
        </div>
    </div>

    <!-- Raretés -->
    <div class="page-section">
        <div class="section-header">
            <div>
                <div class="section-title"><i class="bi bi-stars"></i> Raretés</div>
                <div class="section-meta">De la plus commune à la plus mythique</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
            <?php
            $totalW = array_sum(array_column(RARITIES, 'weight'));
            foreach (RARITIES as $key => $r):
                $pct = round(($r['weight'] / $totalW) * 100, 1);
                $xpBonus = XP_REWARDS[$key] ?? 0;
            ?>
            <div class="panel" style="border-left:3px solid <?= $r['color'] ?>;padding:14px 14px 12px;">
                <div style="font-size:13px;font-weight:700;color:<?= $r['color'] ?>;margin-bottom:6px;">
                    <?= h($r['label']) ?>
                </div>
                <div style="font-size:11px;color:var(--text-4);line-height:1.7;">
                    <div><?= $pct ?>% de chance</div>
                    <div>+<?= $xpBonus + XP_PER_ROLL ?> XP si nouvelle</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Dernières cartes -->
    <?php if (!empty($recent)): ?>
    <div class="page-section">
        <div class="section-header">
            <div>
                <div class="section-title"><i class="bi bi-clock-history"></i> Derniers ajouts</div>
                <div class="section-meta"><?= count($recent) ?> cartes récemment ajoutées</div>
            </div>
            <a href="<?= APP_URL ?>/cards.php" class="btn-secondary btn-sm">
                Voir tout <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="card-grid">
            <?php foreach ($recent as $card): ?>
            <a href="<?= APP_URL ?>/card.php?id=<?= $card['id'] ?>" class="card-item rarity-<?= h($card['rarity']) ?>">
                <div class="card-image-wrap">
                    <?php if ($card['image_file']): ?>
                        <img class="card-thumbnail" src="<?= h(cardImageUrl($card['image_file'])) ?>" alt="<?= h($card['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-no-image"><i class="bi bi-image"></i><span>Pas d'image</span></div>
                    <?php endif; ?>
                    <div class="card-rarity-tag <?= h($card['rarity']) ?>"><?= h(rarityLabel($card['rarity'])) ?></div>
                </div>
                <div class="card-info">
                    <div class="card-info-name"><?= h($card['name']) ?></div>
                    <div class="card-info-char"><?= h($card['character_name']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="page-section">
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-collection empty-state-icon"></i>
                <div class="empty-state-title">Aucune carte pour l'instant</div>
                <p class="empty-state-desc">L'administrateur n'a pas encore ajouté de cartes. Revenez bientôt !</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA si non connecté -->
    <?php if (!isLoggedIn()): ?>
    <div class="panel panel-gold" style="padding:28px;text-align:center;">
        <h2 style="font-family:'Cinzel',serif;font-size:20px;color:var(--text-1);margin-bottom:8px;">
            Prêt à commencer votre collection ?
        </h2>
        <p style="color:var(--text-4);font-size:14px;margin-bottom:20px;max-width:480px;margin-left:auto;margin-right:auto;">
            Connectez-vous avec Discord pour débloquer l'accès complet : rolls quotidiens, système XP, classement et bien plus.
        </p>
        <a href="<?= APP_URL ?>/login.php" class="btn-primary" style="font-size:15px;padding:12px 28px;">
            <i class="bi bi-discord"></i> Connexion Discord gratuite
        </a>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

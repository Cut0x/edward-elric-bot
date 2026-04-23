<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$userId = preg_replace('/\D+/', '', $_GET['id'] ?? '');
if (!$userId) {
    header('Location: ' . APP_URL . '/leaderboard.php');
    exit;
}

$user = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]);
if (!$user) {
    syncDiscordUserProfile($userId);
    $user = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]);
}
if (!$user) {
    header('Location: ' . APP_URL . '/leaderboard.php');
    exit;
}

syncDiscordUserProfile($userId);
$user   = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]) ?: $user;

$tab    = in_array($_GET['tab'] ?? '', ['collection','oeuvres']) ? ($_GET['tab'] ?? 'collection') : 'collection';
$page   = max(1, (int)($_GET['page'] ?? 1));
$stats  = getUserStats($userId);
$xp     = xpInfo((int)($user['xp'] ?? 0));
$badges = userBadges($user, $stats['owned']);
$data   = getUserCollection($userId, ['owned' => 'owned'], $page);

$isAuthor = !empty($user['is_card_author']) || (string)($user['id'] ?? '') === ADMIN_DISCORD_ID;

// Cartes créées
$createdCards = [];
if ($isAuthor) {
    $createdCards = dbQuery(
        "SELECT * FROM cards WHERE author_id = ? AND is_active = 1 ORDER BY rarity_weight DESC",
        [$userId]
    );
}

// Rarity breakdown
$rarityBreakdown = dbQuery(
    "SELECT c.rarity, COUNT(*) as count
     FROM user_cards uc
     JOIN cards c ON c.id = uc.card_id
     WHERE uc.user_id = ?
     GROUP BY c.rarity
     ORDER BY c.rarity_weight DESC",
    [$userId]
);
$breakdownByRarity = [];
foreach ($rarityBreakdown as $row) {
    $breakdownByRarity[$row['rarity']] = (int)$row['count'];
}

define('PAGE_TITLE', 'Profil de ' . userDisplayName($user));
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="profile-banner"
             style="background-image:url('<?= h(getBannerUrl($userId, $user['banner'] ?? null)) ?>');"></div>
        <div class="profile-avatar-wrap">
            <img class="profile-avatar"
                 src="<?= h(getAvatarUrl($userId, $user['avatar'] ?? null)) ?>"
                 alt="<?= h(userDisplayName($user)) ?>"
                 onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
        </div>
        <div class="profile-body">
            <div class="profile-name">
                <?= h(userDisplayName($user)) ?>
                <span class="level-badge <?= levelBadgeClass($xp['level']) ?>">Nv.<?= $xp['level'] ?></span>
                <?php if ($isAuthor): ?>
                    <span class="rarity-badge legendaire" style="font-size:10px;">
                        <i class="bi bi-brush-fill"></i> Artiste
                    </span>
                <?php endif; ?>
            </div>
            <div class="profile-tag">
                @<?= h($user['username']) ?>
                &nbsp;·&nbsp;
                <span style="color:<?= levelColor($xp['level']) ?>;"><?= h(levelTitle($xp['level'])) ?></span>
            </div>

            <?php if (!empty($badges)): ?>
                <div class="profile-badges">
                    <?php foreach ($badges as $badge): ?>
                        <span class="profile-badge-tip" data-tooltip="<?= h($badge['label']) ?>">
                            <img class="profile-badge pixel-icon"
                                 src="<?= h($badge['image']) ?>"
                                 alt="<?= h($badge['label']) ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="profile-meta">
                <div class="profile-stat-pill">
                    <i class="bi bi-collection-fill"></i>
                    <strong><?= $stats['owned'] ?></strong>&nbsp;/ <?= $stats['total'] ?> cartes
                </div>
                <div class="profile-stat-pill">
                    <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:12px;height:12px;" alt="">
                    <strong><?= number_format((int)($user['xp'] ?? 0)) ?></strong>&nbsp;XP
                </div>
                <?php if ($stats['rarest_card']): ?>
                    <div class="profile-stat-pill">
                        <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;" alt="">
                        <span>Meilleure :&nbsp;<strong style="color:<?= rarityColor($stats['rarest_card']['rarity']) ?>;"><?= h($stats['rarest_card']['name']) ?></strong></span>
                    </div>
                <?php endif; ?>
                <?php if ($isAuthor): ?>
                    <div class="profile-stat-pill">
                        <i class="bi bi-brush-fill" style="color:var(--gold-3);"></i>
                        <strong><?= count($createdCards) ?></strong>&nbsp;carte<?= count($createdCards) > 1 ? 's' : '' ?> créée<?= count($createdCards) > 1 ? 's' : '' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- XP bar -->
        <div class="profile-xp-row">
            <span class="profile-level-label" style="color:<?= $xp['color'] ?>;">Nv.<?= $xp['level'] ?></span>
            <div class="xp-bar-wrap" style="flex:1;">
                <img src="<?= ICONS_URL ?>/experience.gif" class="xp-icon pixel-icon" alt="XP">
                <div class="xp-bar-track">
                    <div class="xp-bar-fill" data-xp-width="<?= $xp['percent'] ?>" style="width:0%;"></div>
                </div>
                <span class="xp-text"><?= number_format($xp['progress']) ?> / <?= number_format($xp['needed']) ?> XP</span>
            </div>
            <span class="profile-level-label" style="color:<?= levelColor($xp['level'] + 1) ?>;">Nv.<?= $xp['level'] + 1 ?></span>
        </div>
    </div>

    <!-- Stats grid -->
    <div class="profile-stats-grid">
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-collection-fill"></i></div>
            <div class="stat-block-num"><?= $stats['owned'] ?></div>
            <div class="stat-block-label">Cartes obtenues</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-percent"></i></div>
            <div class="stat-block-num"><?= $stats['percent'] ?>%</div>
            <div class="stat-block-label">Complétude</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-dice-5-fill"></i></div>
            <div class="stat-block-num"><?= number_format($stats['total_rolls']) ?></div>
            <div class="stat-block-label">Rolls total</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:18px;height:18px;" alt="XP"></div>
            <div class="stat-block-num"><?= number_format((int)($user['xp'] ?? 0)) ?></div>
            <div class="stat-block-label">XP totale</div>
        </div>
        <?php if ($isAuthor): ?>
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-brush-fill"></i></div>
            <div class="stat-block-num"><?= count($createdCards) ?></div>
            <div class="stat-block-label">Cartes créées</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rarity breakdown -->
    <?php if ($stats['owned'] > 0): ?>
    <div class="panel" style="margin-bottom:24px;">
        <div class="panel-header">
            <div class="panel-title"><i class="bi bi-bar-chart-fill"></i> Répartition par rareté</div>
        </div>
        <div class="panel-body">
            <div class="rarity-breakdown">
                <?php foreach (RARITIES as $key => $r):
                    $count = $breakdownByRarity[$key] ?? 0;
                    $totalOfRarity = (int)(dbQueryOne("SELECT COUNT(*) as c FROM cards WHERE rarity = ? AND is_active = 1", [$key])['c'] ?? 0);
                    $pct = $totalOfRarity > 0 ? min(100, round(($count / $totalOfRarity) * 100)) : 0;
                ?>
                    <div class="rarity-breakdown-row">
                        <div class="rarity-breakdown-label" style="color:<?= $r['color'] ?>;">
                            <?= h($r['label']) ?>
                        </div>
                        <div class="rarity-breakdown-bar">
                            <div class="rarity-breakdown-fill <?= $key ?>"
                                 data-progress="<?= $pct ?>"
                                 style="width:0%;"></div>
                        </div>
                        <div class="rarity-breakdown-count"><?= $count ?>/<?= $totalOfRarity ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="profile-tabs">
        <a class="profile-tab <?= $tab === 'collection' ? 'active' : '' ?>"
           href="?id=<?= urlencode($userId) ?>&tab=collection">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            Collection
            <span class="tab-badge"><?= $stats['owned'] ?></span>
        </a>
        <?php if ($isAuthor && !empty($createdCards)): ?>
        <a class="profile-tab <?= $tab === 'oeuvres' ? 'active' : '' ?>"
           href="?id=<?= urlencode($userId) ?>&tab=oeuvres">
            <i class="bi bi-brush-fill"></i>
            Œuvres créées
            <span class="tab-badge"><?= count($createdCards) ?></span>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($tab === 'oeuvres' && $isAuthor): ?>
        <!-- Oeuvres -->
        <div class="section-header" style="margin-bottom:16px;">
            <div>
                <div class="section-title"><i class="bi bi-brush-fill"></i> Cartes créées par <?= h(userDisplayName($user)) ?></div>
                <div class="section-meta"><?= count($createdCards) ?> carte<?= count($createdCards) > 1 ? 's' : '' ?> dans la collection officielle</div>
            </div>
        </div>
        <?php if (empty($createdCards)): ?>
            <div class="panel">
                <div class="empty-state">
                    <i class="bi bi-brush empty-state-icon"></i>
                    <div class="empty-state-title">Aucune œuvre</div>
                </div>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($createdCards as $card): ?>
                    <a href="<?= appUrl('/card.php?id=' . $card['id']) ?>"
                       class="card-item rarity-<?= h($card['rarity']) ?>">
                        <div class="card-image-wrap">
                            <?php if ($card['image_file']): ?>
                                <img class="card-thumbnail"
                                     src="<?= h(cardImageUrl($card['image_file'])) ?>"
                                     alt="<?= h($card['name']) ?>" loading="lazy">
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
        <?php endif; ?>

    <?php else: ?>
        <!-- Collection -->
        <div class="section-header" style="margin-bottom:16px;">
            <div>
                <div class="section-title"><i class="bi bi-grid-3x3-gap-fill"></i> Collection publique</div>
                <div class="section-meta"><?= $data['total'] ?> carte<?= $data['total'] > 1 ? 's' : '' ?> obtenue<?= $data['total'] > 1 ? 's' : '' ?></div>
            </div>
        </div>
        <?php if (empty($data['cards'])): ?>
            <div class="panel">
                <div class="empty-state">
                    <i class="bi bi-collection empty-state-icon"></i>
                    <div class="empty-state-title">Collection vide</div>
                    <p class="empty-state-desc">Ce joueur n'a pas encore de cartes.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($data['cards'] as $card): ?>
                    <a href="<?= appUrl('/card.php?id=' . $card['id']) ?>"
                       class="card-item rarity-<?= h($card['rarity']) ?>">
                        <div class="card-image-wrap">
                            <?php if ($card['image_file']): ?>
                                <img class="card-thumbnail"
                                     src="<?= h(cardImageUrl($card['image_file'])) ?>"
                                     alt="<?= h($card['name']) ?>" loading="lazy">
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

            <?php if ($data['pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= urlencode($userId) ?>&page=<?= $page-1 ?>&tab=collection" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1,$page-3); $i <= min($data['pages'],$page+3); $i++): ?>
                        <a href="?id=<?= urlencode($userId) ?>&page=<?= $i ?>&tab=collection"
                           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $data['pages']): ?>
                        <a href="?id=<?= urlencode($userId) ?>&page=<?= $page+1 ?>&tab=collection" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

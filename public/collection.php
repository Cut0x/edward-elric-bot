<?php
define('PAGE_TITLE', 'Ma Collection');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId  = $_SESSION['user_id'];
$page    = max(1, (int)($_GET['page'] ?? 1));
$tab     = in_array($_GET['tab'] ?? '', ['collection', 'oeuvres']) ? ($_GET['tab'] ?? 'collection') : 'collection';
$filters = [
    'rarity' => $_GET['rarity'] ?? '',
    'owned'  => $_GET['owned']  ?? '',
];

$data   = getUserCollection($userId, $filters, $page);
$stats  = getUserStats($userId);
$xp     = $stats['xp_info'];
$user   = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]);
syncDiscordUserProfile($userId);
$user   = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]) ?: $user;
$badges = userBadges($user, $stats['owned']);

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

// Cartes créées (si auteur)
$createdCards = [];
$isAuthor = !empty($user['is_card_author']) || (string)($user['id'] ?? '') === ADMIN_DISCORD_ID;
if ($isAuthor) {
    $createdCards = dbQuery(
        "SELECT * FROM cards WHERE author_id = ? AND is_active = 1 ORDER BY created_at DESC",
        [$userId]
    );
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="profile-banner"
             style="background-image:url('<?= h(getBannerUrl($userId, $user['banner'] ?? null)) ?>');"></div>
        <div class="profile-avatar-wrap">
            <img class="profile-avatar"
                 src="<?= h(getAvatarUrl($userId, $user['avatar'] ?? $_SESSION['avatar'])) ?>"
                 alt="<?= h($_SESSION['username']) ?>"
                 onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
        </div>
        <div class="profile-body">
            <div class="profile-name">
                <?= h($_SESSION['global_name'] ?? $_SESSION['username']) ?>
                <span class="level-badge <?= levelBadgeClass($xp['level']) ?>">
                    Nv.<?= $xp['level'] ?>
                </span>
                <?php if ($isAuthor): ?>
                    <span class="rarity-badge legendaire" style="font-size:10px;">
                        <i class="bi bi-brush-fill"></i> Artiste
                    </span>
                <?php endif; ?>
            </div>
            <div class="profile-tag">
                @<?= h($_SESSION['username']) ?>
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
                    <strong><?= number_format($xp['xp']) ?></strong>&nbsp;XP
                </div>
                <div class="profile-stat-pill">
                    <i class="bi bi-dice-5-fill"></i>
                    <strong><?= $stats['rolls_left'] ?></strong>/<?= MAX_DAILY_ROLLS ?> rolls
                </div>
                <?php if ($stats['rarest_card']): ?>
                    <div class="profile-stat-pill">
                        <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;" alt="">
                        <span>Meilleure :&nbsp;<strong style="color:<?= rarityColor($stats['rarest_card']['rarity']) ?>;"><?= h($stats['rarest_card']['name']) ?></strong></span>
                    </div>
                <?php endif; ?>
                <a class="profile-stat-pill" href="<?= appUrl('/user.php?id=' . urlencode($userId)) ?>" style="text-decoration:none;">
                    <i class="bi bi-person-badge-fill"></i>
                    Profil public
                </a>
                <a class="profile-stat-pill" href="<?= appUrl('/roll') ?>" style="text-decoration:none;">
                    <i class="bi bi-dice-5-fill"></i>
                    Roll du jour
                </a>
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
            <div class="stat-block-label">Progression</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><i class="bi bi-dice-5-fill"></i></div>
            <div class="stat-block-num"><?= number_format($stats['total_rolls']) ?></div>
            <div class="stat-block-label">Rolls total</div>
        </div>
        <div class="stat-block">
            <div class="stat-block-icon"><img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:18px;height:18px;" alt="XP"></div>
            <div class="stat-block-num"><?= number_format($xp['xp']) ?></div>
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
            <span style="font-size:12px;color:var(--text-4);"><?= $stats['owned'] ?> / <?= $stats['total'] ?> cartes</span>
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

    <!-- Collection progress bar -->
    <div class="panel" style="padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:7px;">
                <span style="font-size:12px;color:var(--text-4);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Progression de collection</span>
                <span style="font-size:13px;color:var(--gold-4);font-weight:700;font-family:'JetBrains Mono',monospace;"><?= $stats['percent'] ?>%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" data-progress="<?= $stats['percent'] ?>" style="width:0%;"></div>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:18px;font-weight:700;color:var(--text-1);font-family:'JetBrains Mono',monospace;">
                <?= $stats['owned'] ?> <span style="color:var(--text-4);font-size:13px;">/ <?= $stats['total'] ?></span>
            </div>
            <div style="font-size:11px;color:var(--text-4);margin-top:2px;">
                <?= $stats['total'] - $stats['owned'] ?> manquante<?= ($stats['total'] - $stats['owned']) > 1 ? 's' : '' ?>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="profile-tabs">
        <a class="profile-tab <?= $tab === 'collection' ? 'active' : '' ?>"
           href="?tab=collection">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            Ma Collection
            <span class="tab-badge"><?= $stats['owned'] ?></span>
        </a>
        <?php if ($isAuthor && !empty($createdCards)): ?>
        <a class="profile-tab <?= $tab === 'oeuvres' ? 'active' : '' ?>"
           href="?tab=oeuvres">
            <i class="bi bi-brush-fill"></i>
            Mes Œuvres
            <span class="tab-badge"><?= count($createdCards) ?></span>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($tab === 'oeuvres' && $isAuthor): ?>
        <!-- Oeuvres créées -->
        <div class="section-header" style="margin-bottom:16px;">
            <div>
                <div class="section-title"><i class="bi bi-brush-fill"></i> Cartes que j'ai créées</div>
                <div class="section-meta"><?= count($createdCards) ?> carte<?= count($createdCards) > 1 ? 's' : '' ?> dans la collection officielle</div>
            </div>
        </div>
        <?php if (empty($createdCards)): ?>
            <div class="panel">
                <div class="empty-state">
                    <i class="bi bi-brush empty-state-icon"></i>
                    <div class="empty-state-title">Aucune œuvre pour l'instant</div>
                    <p class="empty-state-desc">Vos cartes apparaîtront ici dès qu'elles seront approuvées et ajoutées à la collection.</p>
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
        <!-- Collection tab -->
        <div class="toolbar" style="margin-bottom:20px;">
            <span class="toolbar-label">Rareté</span>
            <a href="?owned=<?= urlencode($filters['owned']) ?>&tab=collection"
               class="filter-chip <?= $filters['rarity'] === '' ? 'active' : '' ?>">Toutes</a>
            <?php foreach (RARITIES as $key => $r): ?>
                <a href="?rarity=<?= urlencode($key) ?>&owned=<?= urlencode($filters['owned']) ?>&tab=collection"
                   class="filter-chip <?= $filters['rarity'] === $key ? 'active' : '' ?>"
                   style="<?= $filters['rarity'] === $key ? '' : "color:{$r['color']};" ?>">
                    <?= h($r['label']) ?>
                </a>
            <?php endforeach; ?>

            <div class="toolbar-sep"></div>
            <span class="toolbar-label">Statut</span>
            <a href="?rarity=<?= urlencode($filters['rarity']) ?>&tab=collection"
               class="filter-chip <?= $filters['owned'] === '' ? 'active' : '' ?>">Toutes</a>
            <a href="?rarity=<?= urlencode($filters['rarity']) ?>&owned=owned&tab=collection"
               class="filter-chip <?= $filters['owned'] === 'owned' ? 'active' : '' ?>">
                <i class="bi bi-check-circle-fill" style="color:var(--green);"></i> Obtenues
            </a>
            <a href="?rarity=<?= urlencode($filters['rarity']) ?>&owned=missing&tab=collection"
               class="filter-chip <?= $filters['owned'] === 'missing' ? 'active' : '' ?>">
                <i class="bi bi-lock-fill"></i> Manquantes
            </a>
        </div>

        <div style="font-size:12px;color:var(--text-4);margin-bottom:14px;">
            <?= $data['total'] ?> carte<?= $data['total'] > 1 ? 's' : '' ?>
        </div>

        <?php if (empty($data['cards'])): ?>
            <div class="panel">
                <div class="empty-state">
                    <i class="bi bi-collection empty-state-icon"></i>
                    <div class="empty-state-title">
                        <?php if ($filters['owned'] === 'owned'): ?>Vous n'avez pas encore de carte
                        <?php elseif ($filters['owned'] === 'missing'): ?>Collection complète pour ce filtre !
                        <?php else: ?>Aucune carte trouvée
                        <?php endif; ?>
                    </div>
                    <p class="empty-state-desc">
                        <?php if ($filters['owned'] === 'owned'): ?>
                            Utilisez <code class="cmd">/roll</code> sur Discord ou la page Roll pour obtenir vos premières cartes.
                        <?php elseif ($filters['owned'] === 'missing'): ?>
                            Félicitations, vous avez toutes les cartes de cette catégorie !
                        <?php else: ?>
                            Essayez de modifier vos filtres.
                        <?php endif; ?>
                    </p>
                    <?php if ($filters['rarity'] || $filters['owned']): ?>
                        <a href="<?= appUrl('/collection') ?>" class="btn-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Effacer les filtres
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($data['cards'] as $card): ?>
                    <a href="<?= appUrl('/card.php?id=' . $card['id']) ?>"
                       class="card-item rarity-<?= h($card['rarity']) ?> <?= !$card['owned'] ? 'is-locked' : '' ?>">
                        <div class="card-image-wrap">
                            <?php if ($card['image_file']): ?>
                                <img class="card-thumbnail"
                                     src="<?= h(cardImageUrl($card['image_file'])) ?>"
                                     alt="<?= h($card['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card-no-image"><i class="bi bi-image"></i><span>Pas d'image</span></div>
                            <?php endif; ?>
                            <div class="card-rarity-tag <?= h($card['rarity']) ?>"><?= h(rarityLabel($card['rarity'])) ?></div>
                            <?php if ($card['owned']): ?>
                                <div class="card-owned-check"><i class="bi bi-check2"></i></div>
                            <?php else: ?>
                                <div class="card-locked"><i class="bi bi-lock-fill"></i></div>
                            <?php endif; ?>
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
                        <a href="?page=<?= $page-1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>&tab=collection" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1,$page-3); $i <= min($data['pages'],$page+3); $i++): ?>
                        <a href="?page=<?= $i ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>&tab=collection"
                           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $data['pages']): ?>
                        <a href="?page=<?= $page+1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>&tab=collection" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

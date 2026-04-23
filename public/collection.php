<?php
define('PAGE_TITLE', 'Ma Collection');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId  = $_SESSION['user_id'];
$page    = max(1, (int)($_GET['page'] ?? 1));
$filters = [
    'rarity' => $_GET['rarity'] ?? '',
    'owned'  => $_GET['owned']  ?? '',
];

$data  = getUserCollection($userId, $filters, $page);
$stats = getUserStats($userId);
$xp    = $stats['xp_info'];
$user  = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]);
syncDiscordUserProfile($userId);
$user  = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]) ?: $user;
$badges = userBadges($user, $stats['owned']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <!-- Profile hero -->
    <div class="profile-hero">
        <div class="profile-banner" style="background-image:url('<?= h(getBannerUrl($userId, $user['banner'] ?? null)) ?>');"></div>
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
            </div>
            <div class="profile-tag">@<?= h($_SESSION['username']) ?> &nbsp;·&nbsp; <?= levelTitle($xp['level']) ?></div>
            <?php if (!empty($badges)): ?>
                <div class="profile-badges">
                    <?php foreach ($badges as $badge): ?>
                        <span class="profile-badge-tip" data-tooltip="<?= h($badge['label']) ?>">
                            <img class="profile-badge" src="<?= h($badge['image']) ?>" alt="<?= h($badge['label']) ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="profile-meta">
                <div class="profile-stat-pill">
                    <i class="bi bi-collection-fill"></i>
                    <strong><?= $stats['owned'] ?></strong> / <?= $stats['total'] ?> cartes
                </div>
                <div class="profile-stat-pill">
                    <i class="bi bi-dice-5-fill"></i>
                    <strong><?= $stats['rolls_left'] ?></strong>/<?= MAX_DAILY_ROLLS ?> rolls restants
                </div>
                <a class="profile-stat-pill" href="<?= APP_URL ?>/roll.php" style="text-decoration:none;">
                    <i class="bi bi-dice-5-fill"></i>
                    <span>Roll sur le site</span>
                </a>
                <?php if ($stats['rarest_card']): ?>
                <div class="profile-stat-pill">
                    <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;" alt="">
                    <span>Meilleure : <strong><?= h($stats['rarest_card']['name']) ?></strong></span>
                </div>
                <?php endif; ?>
                <a class="profile-stat-pill" href="<?= APP_URL ?>/user.php?id=<?= urlencode($userId) ?>" style="text-decoration:none;">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Profil public</span>
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

    <!-- Progression bar -->
    <div class="panel" style="padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                <span style="font-size:12px;color:var(--text-4);font-weight:500;">Progression de collection</span>
                <span style="font-size:12px;color:var(--gold-4);font-weight:700;font-family:'JetBrains Mono',monospace;"><?= $stats['percent'] ?>%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" data-progress="<?= $stats['percent'] ?>" style="width:0%;"></div>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:16px;font-weight:700;color:var(--text-1);font-family:'JetBrains Mono',monospace;">
                <?= $stats['owned'] ?> <span style="color:var(--text-4);font-size:13px;">/ <?= $stats['total'] ?></span>
            </div>
            <div style="font-size:11px;color:var(--text-4);"><?= $stats['total'] - $stats['owned'] ?> manquante<?= ($stats['total'] - $stats['owned']) > 1 ? 's' : '' ?></div>
        </div>
    </div>

    <!-- Toolbar filters -->
    <div class="toolbar" style="margin-bottom:20px;">
        <span class="toolbar-label">Rareté</span>
        <a href="?owned=<?= urlencode($filters['owned']) ?>"
           class="filter-chip <?= $filters['rarity'] === '' ? 'active' : '' ?>">Toutes</a>
        <?php foreach (RARITIES as $key => $r): ?>
            <a href="?rarity=<?= urlencode($key) ?>&owned=<?= urlencode($filters['owned']) ?>"
               class="filter-chip <?= $filters['rarity'] === $key ? 'active' : '' ?>"
               style="<?= $filters['rarity'] === $key ? '' : "color:{$r['color']};" ?>">
                <?= h($r['label']) ?>
            </a>
        <?php endforeach; ?>

        <div class="toolbar-sep"></div>
        <span class="toolbar-label">Statut</span>
        <a href="?rarity=<?= urlencode($filters['rarity']) ?>"
           class="filter-chip <?= $filters['owned'] === '' ? 'active' : '' ?>">Toutes</a>
        <a href="?rarity=<?= urlencode($filters['rarity']) ?>&owned=owned"
           class="filter-chip <?= $filters['owned'] === 'owned' ? 'active' : '' ?>">
            <i class="bi bi-check-circle-fill" style="color:var(--green);"></i> Obtenues
        </a>
        <a href="?rarity=<?= urlencode($filters['rarity']) ?>&owned=missing"
           class="filter-chip <?= $filters['owned'] === 'missing' ? 'active' : '' ?>">
            <i class="bi bi-lock-fill"></i> Manquantes
        </a>
    </div>

    <div style="font-size:12px;color:var(--text-4);margin-bottom:12px;">
        <?= $data['total'] ?> carte<?= $data['total'] > 1 ? 's' : '' ?>
    </div>

    <!-- Cards Grid -->
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
                        Utilisez <code class="cmd">/roll</code> sur Discord pour obtenir vos premières cartes.
                    <?php elseif ($filters['owned'] === 'missing'): ?>
                        Vous avez toutes les cartes de cette catégorie !
                    <?php else: ?>
                        Essayez de modifier vos filtres.
                    <?php endif; ?>
                </p>
                <?php if ($filters['rarity'] || $filters['owned']): ?>
                    <a href="<?= APP_URL ?>/collection.php" class="btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer les filtres
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($data['cards'] as $card): ?>
                <a href="<?= APP_URL ?>/card.php?id=<?= $card['id'] ?>"
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
                    <a href="?page=<?= $page-1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = max(1,$page-3); $i <= min($data['pages'],$page+3); $i++): ?>
                    <a href="?page=<?= $i ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $data['pages']): ?>
                    <a href="?page=<?= $page+1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&owned=<?= urlencode($filters['owned']) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

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
$user = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]) ?: $user;

$page = max(1, (int)($_GET['page'] ?? 1));
$stats = getUserStats($userId);
$xp = xpInfo((int)($user['xp'] ?? 0));
$badges = userBadges($user, $stats['owned']);
$data = getUserCollection($userId, ['owned' => 'owned'], $page);

define('PAGE_TITLE', 'Collection de ' . userDisplayName($user));
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">
    <div class="profile-hero">
        <div class="profile-banner" style="background-image:url('<?= h(getBannerUrl($userId, $user['banner'] ?? null)) ?>');"></div>
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
            </div>
            <div class="profile-tag">@<?= h($user['username']) ?> &nbsp;·&nbsp; <?= h(levelTitle($xp['level'])) ?></div>
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
                <div class="profile-stat-pill"><i class="bi bi-collection-fill"></i><strong><?= $stats['owned'] ?></strong> / <?= $stats['total'] ?> cartes</div>
                <div class="profile-stat-pill"><img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:12px;height:12px;" alt=""><strong><?= number_format((int)($user['xp'] ?? 0)) ?></strong> XP</div>
                <?php if ($stats['rarest_card']): ?>
                    <div class="profile-stat-pill"><img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;" alt=""><span>Meilleure : <strong><?= h($stats['rarest_card']['name']) ?></strong></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section-header mb-24">
        <div>
            <div class="section-title"><i class="bi bi-grid-3x3-gap-fill"></i> Collection publique</div>
            <div class="section-meta"><?= $data['total'] ?> carte<?= $data['total'] > 1 ? 's' : '' ?> obtenue<?= $data['total'] > 1 ? 's' : '' ?></div>
        </div>
    </div>

    <?php if (empty($data['cards'])): ?>
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-collection empty-state-icon"></i>
                <div class="empty-state-title">Aucune carte publique</div>
                <p class="empty-state-desc">Cette collection est encore vide.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($data['cards'] as $card): ?>
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

        <?php if ($data['pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = max(1, $page - 3); $i <= min($data['pages'], $page + 3); $i++): ?>
                    <a href="?id=<?= urlencode($userId) ?>&page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
define('PAGE_TITLE', 'Joueurs');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$users = dbQuery(
    'SELECT u.id, u.username, u.global_name, u.avatar, u.banner, u.xp, u.is_owner, u.is_card_author,
            COUNT(DISTINCT uc.card_id) as owned_count
     FROM users u
     LEFT JOIN user_cards uc ON uc.user_id = u.id
     GROUP BY u.id
     ORDER BY RAND()
     LIMIT 48'
);

$totalCards = (int)(dbQueryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1')['c'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">
    <div class="section-header mb-24">
        <div>
            <div class="section-title"><i class="bi bi-people-fill"></i> Joueurs</div>
            <div class="section-meta"><?= count($users) ?> profils affichés aléatoirement</div>
        </div>
        <a href="<?= APP_URL ?>/users.php" class="btn-secondary btn-sm"><i class="bi bi-shuffle"></i> Mélanger</a>
    </div>

    <?php if (empty($users)): ?>
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-people empty-state-icon"></i>
                <div class="empty-state-title">Aucun joueur</div>
                <p class="empty-state-desc">Les profils apparaîtront ici dès que des utilisateurs auront utilisé le bot.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="user-random-grid">
            <?php foreach ($users as $user):
                $xp = xpInfo((int)($user['xp'] ?? 0));
                $pct = $totalCards > 0 ? round(((int)$user['owned_count'] / $totalCards) * 100, 1) : 0;
                $badges = userBadges($user, (int)$user['owned_count']);
            ?>
                <a class="user-random-card" href="<?= APP_URL ?>/user.php?id=<?= urlencode($user['id']) ?>">
                    <div class="user-random-banner" style="background-image:url('<?= h(getBannerUrl($user['id'], $user['banner'] ?? null, 512)) ?>');"></div>
                    <div class="user-random-body">
                        <img class="user-random-avatar"
                             src="<?= h(getAvatarUrl($user['id'], $user['avatar'] ?? null, 128)) ?>"
                             alt="<?= h(userDisplayName($user)) ?>"
                             onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                        <div class="user-random-name"><?= h(userDisplayName($user)) ?></div>
                        <div class="user-random-meta">
                            <span>Nv.<?= $xp['level'] ?></span>
                            <span><?= (int)$user['owned_count'] ?>/<?= $totalCards ?> cartes</span>
                            <span><?= $pct ?>%</span>
                        </div>
                        <?php if (!empty($badges)): ?>
                            <div class="profile-badges">
                                <?php foreach (array_slice($badges, 0, 5) as $badge): ?>
                                    <span class="profile-badge-tip" data-tooltip="<?= h($badge['label']) ?>">
                                        <img class="profile-badge" src="<?= h($badge['image']) ?>" alt="<?= h($badge['label']) ?>">
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

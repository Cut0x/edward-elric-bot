<?php
define('PAGE_TITLE', 'Admin');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

if (!isOwner()) {
    header('Location: ' . APP_URL . '/admin/cards.php');
    exit;
}

$stats       = getStats();
$rollsToday  = (int)(dbQueryOne('SELECT COUNT(*) as c FROM roll_history WHERE DATE(rolled_at) = CURDATE()')['c'] ?? 0);
$recentCards = dbQuery('SELECT c.*, u.username as author_username, u.global_name as author_name FROM cards c LEFT JOIN users u ON u.id = c.author_id ORDER BY c.created_at DESC LIMIT 5');
$recentUsers = dbQuery('SELECT * FROM users ORDER BY created_at DESC LIMIT 5');
$topLevel    = dbQuery('SELECT id, username, global_name, avatar, level, xp FROM users ORDER BY xp DESC LIMIT 3');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-section">Principal</div>
        <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-item active">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/admin/cards.php" class="admin-nav-item">
            <i class="bi bi-collection-fill"></i> Cartes
        </a>
        <a href="<?= APP_URL ?>/admin/add.php" class="admin-nav-item">
            <i class="bi bi-plus-circle-fill"></i> Ajouter
        </a>
        <?php if (isOwner()): ?>
            <a href="<?= APP_URL ?>/admin/users.php" class="admin-nav-item">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
        <?php endif; ?>
        <div class="admin-sidebar-section">Accès rapide</div>
        <a href="<?= APP_URL ?>/cards.php" class="admin-nav-item" target="_blank">
            <i class="bi bi-eye-fill"></i> Voir le site
        </a>
        <a href="<?= APP_URL ?>/index.php" class="admin-nav-item">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </aside>

    <main class="admin-content">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Dashboard</h1>
                <p class="admin-page-subtitle">
                    Bonjour, <?= h($_SESSION['global_name'] ?? $_SESSION['username']) ?>
                    <span style="color:var(--gold-3);margin-left:6px;"><i class="bi bi-shield-lock-fill"></i> <?= isOwner() ? 'Owner' : 'Auteur de carte' ?></span>
                </p>
            </div>
            <a href="<?= APP_URL ?>/admin/add.php" class="btn-gold btn-sm">
                <i class="bi bi-plus-lg"></i> Nouvelle carte
            </a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-block">
                <div class="stat-block-icon"><i class="bi bi-collection-fill"></i></div>
                <div class="stat-block-num"><?= $stats['total_cards'] ?></div>
                <div class="stat-block-label">Cartes totales</div>
            </div>
            <div class="stat-block">
                <div class="stat-block-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-block-num"><?= $stats['total_users'] ?></div>
                <div class="stat-block-label">Joueurs inscrits</div>
            </div>
            <div class="stat-block">
                <div class="stat-block-icon"><i class="bi bi-dice-5-fill"></i></div>
                <div class="stat-block-num"><?= $rollsToday ?></div>
                <div class="stat-block-label">Rolls aujourd'hui</div>
            </div>
            <div class="stat-block">
                <div class="stat-block-icon">
                    <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:18px;" alt="XP">
                </div>
                <div class="stat-block-num"><?= number_format($stats['total_rolls']) ?></div>
                <div class="stat-block-label">Rolls totaux</div>
            </div>
        </div>

        <!-- Two columns -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            <!-- Recent cards -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="bi bi-collection-fill"></i> Dernières cartes</div>
                    <a href="<?= APP_URL ?>/admin/add.php" class="btn-gold btn-sm">+ Ajouter</a>
                </div>
                <?php if (empty($recentCards)): ?>
                    <div class="empty-state" style="padding:28px;">
                        <i class="bi bi-collection empty-state-icon" style="font-size:28px;"></i>
                        <div class="empty-state-title" style="font-size:13px;">Aucune carte</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentCards as $card): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--b-1);">
                            <div class="admin-thumb" style="width:32px;height:42px;border-radius:var(--radius-xs);overflow:hidden;background:var(--bg-300);flex-shrink:0;">
                                <?php if ($card['image_file']): ?>
                                    <img src="<?= h(cardImageUrl($card['image_file'])) ?>"
                                         style="width:100%;height:100%;object-fit:cover;" class="card-thumbnail"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:var(--text-1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= h($card['name']) ?>
                                </div>
                                <span class="rarity-badge <?= h($card['rarity']) ?>" style="font-size:9px;padding:1px 5px;">
                                    <?= rarityLabel($card['rarity']) ?>
                                </span>
                                <?php if (!empty($card['author_username'])): ?>
                                    <span style="font-size:10px;color:var(--text-5);margin-left:4px;">par <?= h($card['author_name'] ?: $card['author_username']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $card['id'] ?>"
                               style="color:var(--text-4);font-size:14px;flex-shrink:0;"
                               title="Modifier"><i class="bi bi-pencil-fill"></i></a>
                        </div>
                    <?php endforeach; ?>
                    <div style="padding:10px 14px;">
                        <a href="<?= APP_URL ?>/admin/cards.php" style="font-size:12px;color:var(--blurple-hover);">
                            Voir toutes les cartes <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top players -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="bi bi-people-fill"></i> Top joueurs</div>
                </div>
                <?php if (empty($recentUsers)): ?>
                    <div class="empty-state" style="padding:28px;">
                        <i class="bi bi-people empty-state-icon" style="font-size:28px;"></i>
                        <div class="empty-state-title" style="font-size:13px;">Aucun joueur</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($topLevel as $u):
                        $xpData = xpInfo((int)($u['xp'] ?? 0));
                    ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--b-1);">
                            <img src="<?= h(getAvatarUrl($u['id'] ?? '0', $u['avatar'])) ?>"
                                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--b-2);"
                                 onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:var(--text-1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= h($u['global_name'] ?? $u['username']) ?>
                                </div>
                                <div style="font-size:11px;color:var(--text-4);">
                                    <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:10px;height:10px;vertical-align:middle;" alt="">
                                    <?= number_format($u['xp'] ?? 0) ?> XP
                                </div>
                            </div>
                            <span class="level-badge <?= levelBadgeClass($xpData['level']) ?>">
                                Nv.<?= $xpData['level'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id   = (int)($_GET['id'] ?? 0);
$card = dbQueryOne(
    'SELECT c.*, u.username as author_username, u.global_name as author_name, u.id as author_user_id
     FROM cards c
     LEFT JOIN users u ON u.id = c.author_id
     WHERE c.id = ? AND c.is_active = 1',
    [$id]
);

if (!$card) {
    header('Location: ' . APP_URL . '/cards.php');
    exit;
}

$isOwned    = false;
$obtainedAt = null;
if (isLoggedIn()) {
    $row        = dbQueryOne('SELECT obtained_at FROM user_cards WHERE user_id = ? AND card_id = ?', [$_SESSION['user_id'], $id]);
    $isOwned    = (bool)$row;
    $obtainedAt = $row['obtained_at'] ?? null;
}

$ownerCount = (int)(dbQueryOne('SELECT COUNT(*) as c FROM user_cards WHERE card_id = ?', [$id])['c'] ?? 0);
$totalUsers = max(1, (int)(dbQueryOne('SELECT COUNT(*) as c FROM users')['c'] ?? 1));

$rarityInfo = RARITIES[$card['rarity']] ?? RARITIES['commune'];
$totalW     = array_sum(array_column(RARITIES, 'weight'));
$rarityPct  = round(($rarityInfo['weight'] / $totalW) * 100, 2);

define('PAGE_TITLE', $card['name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <!-- Back -->
    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-4);margin-bottom:24px;text-decoration:none;transition:color var(--t-fast);"
       onmouseover="this.style.color='var(--text-1)'" onmouseout="this.style.color='var(--text-4)'">
        <i class="bi bi-arrow-left"></i> Retour
    </a>

    <div class="card-detail-layout">

        <!-- Image -->
        <div>
            <div class="card-detail-image-wrap rarity-<?= h($card['rarity']) ?>">
                <?php if ($card['image_file']): ?>
                    <img src="<?= h(cardImageUrl($card['image_file'])) ?>"
                         alt="<?= h($card['name']) ?>">
                <?php else: ?>
                    <div class="card-no-image" style="position:absolute;inset:0;aspect-ratio:unset;">
                        <i class="bi bi-image" style="font-size:48px;"></i>
                        <span>Pas d'image</span>
                    </div>
                <?php endif; ?>
                <div class="card-detail-rarity-bar" style="background:<?= $rarityInfo['color'] ?>;"></div>
            </div>

            <!-- Status -->
            <div style="margin-top:14px;padding:12px 14px;background:var(--bg-400);border-radius:var(--radius-md);border:1px solid var(--b-1);text-align:center;">
                <?php if (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/login.php" class="btn-primary btn-sm" style="width:100%;justify-content:center;">
                        <i class="bi bi-discord"></i> Connexion pour collectionner
                    </a>
                <?php elseif ($isOwned): ?>
                    <div style="color:var(--green);font-size:14px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="bi bi-check-circle-fill"></i> Carte obtenue
                    </div>
                    <?php if ($obtainedAt): ?>
                        <div style="font-size:11px;color:var(--text-4);margin-top:3px;">
                            le <?= date('d/m/Y', strtotime($obtainedAt)) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="color:var(--text-4);font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="bi bi-lock-fill"></i> Non obtenue — utilisez <code class="cmd">/roll</code>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div>
            <!-- Rarity badge -->
            <div style="margin-bottom:8px;">
                <span class="rarity-badge <?= h($card['rarity']) ?>">
                    <?php if ($card['rarity'] === 'legendaire'): ?>
                        <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;" alt="">
                    <?php endif; ?>
                    <?= h($rarityInfo['label']) ?>
                </span>
            </div>

            <!-- Title -->
            <h1 style="font-family:'Cinzel',serif;font-size:clamp(22px,4vw,32px);font-weight:900;color:var(--text-1);margin-bottom:4px;line-height:1.1;">
                <?= h($card['name']) ?>
            </h1>
            <p style="font-size:16px;color:var(--text-3);margin-bottom:6px;"><?= h($card['character_name']) ?></p>
            <p style="font-size:12px;color:var(--text-5);margin-bottom:24px;font-family:'JetBrains Mono',monospace;">
                <?= h($card['serie']) ?>
            </p>

            <!-- Description -->
            <?php if ($card['description']): ?>
            <div class="panel panel-gold" style="margin-bottom:20px;">
                <div class="panel-body">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-5);margin-bottom:8px;">Description</div>
                    <p style="font-size:14px;color:var(--text-2);line-height:1.7;"><?= nl2br(h($card['description'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats grid -->
            <div class="card-detail-info-grid">
                <div class="info-block">
                    <div class="info-block-label">Rareté</div>
                    <div class="info-block-value" style="color:<?= $rarityInfo['color'] ?>;"><?= h($rarityInfo['label']) ?></div>
                    <div class="info-block-sub"><?= $rarityPct ?>% de chance au roll</div>
                </div>
                <div class="info-block">
                    <div class="info-block-label">Série</div>
                    <div class="info-block-value" style="font-size:13px;"><?= h($card['serie']) ?></div>
                </div>
                <div class="info-block">
                    <div class="info-block-label">Auteur</div>
                    <div class="info-block-value" style="font-size:13px;">
                        <?php if (!empty($card['author_user_id'])): ?>
                            <a href="<?= APP_URL ?>/user.php?id=<?= urlencode($card['author_user_id']) ?>" style="color:inherit;text-decoration:none;">
                                <?= h($card['author_name'] ?: $card['author_username']) ?>
                            </a>
                        <?php else: ?>
                            Inconnu
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-block">
                    <div class="info-block-label">Propriétaires</div>
                    <div class="info-block-value"><?= $ownerCount ?></div>
                    <div class="info-block-sub"><?= round(($ownerCount / $totalUsers) * 100, 1) ?>% des joueurs</div>
                </div>
                <div class="info-block">
                    <div class="info-block-label">XP gagné</div>
                    <div class="info-block-value" style="display:flex;align-items:center;gap:5px;">
                        <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:14px;height:14px;" alt="XP">
                        +<?= XP_PER_ROLL + (XP_REWARDS[$card['rarity']] ?? 0) ?>
                    </div>
                    <div class="info-block-sub">si nouvelle carte</div>
                </div>
            </div>

            <!-- Roll command hint -->
            <div style="background:var(--bg-300);border:1px solid var(--b-2);border-radius:var(--radius-md);padding:12px 14px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-dice-5-fill" style="font-size:18px;color:var(--gold-3);flex-shrink:0;"></i>
                <div style="font-size:13px;color:var(--text-4);">
                    Obtenez cette carte en utilisant <code class="cmd">/roll</code> sur Discord.
                    Vous avez <strong style="color:var(--text-2);"><?= MAX_DAILY_ROLLS ?> rolls</strong> par jour.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

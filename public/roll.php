<?php
define('PAGE_TITLE', 'Roll');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$result = null;
$stats = getUserStats($_SESSION['user_id']);
$icon = fn(string $name, string $class = 'roll-ui-icon') => '<img src="' . ICONS_URL . '/' . $name . '" class="' . $class . '" alt="">';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = rollCardForUser($_SESSION['user_id']);
    $stats = getUserStats($_SESSION['user_id']);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content" style="max-width:920px;">
    <div class="section-header mb-24">
        <div>
            <div class="section-title"><?= $icon('reroll.gif') ?> Roll une carte</div>
            <div class="section-meta"><?= $stats['rolls_left'] ?>/<?= MAX_DAILY_ROLLS ?> rolls disponibles aujourd'hui</div>
        </div>
        <a href="<?= APP_URL ?>/collection.php" class="btn-secondary btn-sm"><?= $icon('library.png', 'btn-icon-img') ?> Collection</a>
    </div>

    <?php if ($result && !$result['success']): ?>
        <div class="panel" style="padding:22px;margin-bottom:18px;">
            <div class="empty-state" style="padding:18px;">
                <img src="<?= ICONS_URL ?>/time.gif" class="empty-state-img" alt="">
                <div class="empty-state-title">
                    <?= $result['reason'] === 'no_rolls' ? 'Plus de rolls disponibles' : 'Aucune carte disponible' ?>
                </div>
                <p class="empty-state-desc">
                    <?= $result['reason'] === 'no_rolls'
                        ? 'Prochain rechargement dans ' . h($result['time_left'] ?? 'quelques heures') . '.'
                        : 'Revenez quand des cartes auront été ajoutées.' ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($result && $result['success']):
        $card = $result['card'];
        $rarity = RARITIES[$card['rarity']] ?? RARITIES['commune'];
    ?>
        <div class="roll-result-layout">
            <div class="roll-result-card rarity-<?= h($card['rarity']) ?>">
                <?php if ($card['image_file']): ?>
                    <img src="<?= h(cardImageUrl($card['image_file'])) ?>" alt="<?= h($card['name']) ?>">
                <?php else: ?>
                    <div class="card-no-image"><i class="bi bi-image"></i><span>Pas d'image</span></div>
                <?php endif; ?>
            </div>
            <div class="panel roll-result-info">
                <div class="rarity-badge <?= h($card['rarity']) ?>"><?= h($rarity['label']) ?></div>
                <h1><?= h($card['name']) ?></h1>
                <p><?= h($card['character_name']) ?></p>
                <div class="roll-result-stats">
                    <div><span><?= $icon('experience.gif') ?> XP gagné</span><strong>+<?= (int)$result['xp_gained'] ?></strong></div>
                    <div><span><?= $icon('reroll.gif') ?> Rerolls</span><strong><?= (int)$result['rolls_remaining'] ?>/<?= MAX_DAILY_ROLLS ?></strong></div>
                    <div><span><?= $icon($result['duplicate'] ? 'duplicate.png' : 'success.png') ?> Statut</span><strong><?= $result['duplicate'] ? 'Doublon' : 'Nouvelle' ?></strong></div>
                    <div><span><?= $icon('star.png') ?> Rareté</span><strong><?= h($rarity['label']) ?></strong></div>
                    <div><span><?= $icon('rank.png') ?> Niveau</span><strong><?= (int)$result['level'] ?></strong></div>
                    <div><span><?= $icon('library.png') ?> Collection</span><strong><?= $stats['owned'] ?>/<?= $stats['total'] ?></strong></div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="panel roll-empty-panel">
            <img src="<?= ICONS_URL ?>/reroll.gif" class="roll-empty-icon" alt="">
            <h1>Prêt à roll ?</h1>
            <p>Chaque tirage consomme un roll quotidien et peut ajouter une nouvelle carte à votre collection.</p>
        </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-gold" <?= $stats['rolls_left'] <= 0 ? 'disabled style="opacity:.55;cursor:not-allowed;"' : '' ?>>
            <?= $icon('reroll.gif', 'btn-icon-img') ?>
            <?= $result ? 'Reroll' : 'Roll' ?>
        </button>
        <a href="<?= APP_URL ?>/collection.php" class="btn-secondary"><?= $icon('library.png', 'btn-icon-img') ?> Voir ma collection</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

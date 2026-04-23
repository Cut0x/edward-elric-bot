<?php
define('PAGE_TITLE', 'Cartes');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$filters = [
    'rarity' => $_GET['rarity'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];

$data     = getAllCards($filters, $page);
$ownedIds = [];

if (isLoggedIn()) {
    $rows     = dbQuery('SELECT card_id FROM user_cards WHERE user_id = ?', [$_SESSION['user_id']]);
    $ownedIds = array_column($rows, 'card_id');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <div class="section-header">
        <div>
            <div class="section-title"><i class="bi bi-collection-fill"></i> Collection complète</div>
            <div class="section-meta">
                <?= $data['total'] ?> carte<?= $data['total'] > 1 ? 's' : '' ?> disponibles
                <?php if (isLoggedIn()): ?>
                    &nbsp;·&nbsp; Vous en possédez <strong style="color:var(--gold-4);"><?= count($ownedIds) ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <form method="GET" id="filter-form">
        <div class="toolbar">
            <!-- Search -->
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" id="search-live" name="search"
                       placeholder="Rechercher un personnage, une carte…"
                       value="<?= h($filters['search']) ?>">
                <?php if ($filters['rarity']): ?>
                    <input type="hidden" name="rarity" value="<?= h($filters['rarity']) ?>">
                <?php endif; ?>
            </div>

            <div class="toolbar-sep"></div>
            <span class="toolbar-label">Rareté</span>

            <a href="?search=<?= urlencode($filters['search']) ?>"
               class="filter-chip <?= $filters['rarity'] === '' ? 'active' : '' ?>">Toutes</a>
            <?php foreach (RARITIES as $key => $r): ?>
                <a href="?rarity=<?= urlencode($key) ?>&search=<?= urlencode($filters['search']) ?>"
                   class="filter-chip <?= $filters['rarity'] === $key ? 'active' : '' ?>"
                   style="<?= $filters['rarity'] === $key ? '' : "color:{$r['color']};" ?>">
                    <?= h($r['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </form>

    <!-- Grid -->
    <?php if (empty($data['cards'])): ?>
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-<?= ($filters['search'] || $filters['rarity']) ? 'search' : 'collection' ?> empty-state-icon"></i>
                <div class="empty-state-title">
                    <?= ($filters['search'] || $filters['rarity']) ? 'Aucun résultat' : 'Aucune carte disponible' ?>
                </div>
                <p class="empty-state-desc">
                    <?php if ($filters['search'] || $filters['rarity']): ?>
                        Aucune carte ne correspond à vos filtres. Essayez une autre recherche.
                    <?php else: ?>
                        Les cartes seront ajoutées prochainement par l'administrateur.
                    <?php endif; ?>
                </p>
                <?php if ($filters['search'] || $filters['rarity']): ?>
                    <a href="<?= APP_URL ?>/cards.php" class="btn-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Effacer les filtres
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($data['cards'] as $card):
                $isOwned = isLoggedIn() && in_array((int)$card['id'], $ownedIds, true);
            ?>
                <a href="<?= APP_URL ?>/card.php?id=<?= $card['id'] ?>"
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
                        <?php if ($isOwned): ?>
                            <div class="card-owned-check"><i class="bi bi-check2"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-info">
                        <div class="card-info-name"><?= h($card['name']) ?></div>
                        <div class="card-info-char"><?= h($card['character_name']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($data['pages'] > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&search=<?= urlencode($filters['search']) ?>" class="page-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 3); $i <= min($data['pages'], $page + 3); $i++): ?>
                    <a href="?page=<?= $i ?>&rarity=<?= urlencode($filters['rarity']) ?>&search=<?= urlencode($filters['search']) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $data['pages']): ?>
                    <a href="?page=<?= $page+1 ?>&rarity=<?= urlencode($filters['rarity']) ?>&search=<?= urlencode($filters['search']) ?>" class="page-btn">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

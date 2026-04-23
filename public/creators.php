<?php
define('PAGE_TITLE', 'Artistes & Créateurs');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Récupérer tous les auteurs avec leurs cartes
$creators = dbQuery(
    "SELECT u.id, u.username, u.global_name, u.avatar, u.banner,
            COUNT(c.id) as cards_count,
            GROUP_CONCAT(c.image_file ORDER BY c.rarity_weight DESC SEPARATOR '|||') as preview_images,
            GROUP_CONCAT(c.rarity ORDER BY c.rarity_weight DESC SEPARATOR '|||') as preview_rarities,
            GROUP_CONCAT(c.name ORDER BY c.rarity_weight DESC SEPARATOR '|||') as preview_names
     FROM users u
     JOIN cards c ON c.author_id = u.id AND c.is_active = 1
     GROUP BY u.id
     ORDER BY cards_count DESC"
);

// Totaux pour le hero
$totalCreators = count($creators);
$totalAuthoredCards = array_sum(array_column($creators, 'cards_count'));

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <!-- Hero -->
    <div class="creator-hero">
        <div style="position:relative;z-index:1;">
            <div class="page-header-kicker" style="margin-bottom:8px;">
                <i class="bi bi-brush-fill"></i> Ateliers des alchimistes
            </div>
            <h1 class="font-cinzel" style="font-size:clamp(26px,4vw,38px);margin-bottom:10px;">
                Artistes &amp; Créateurs
            </h1>
            <p style="color:var(--text-3);font-size:15px;line-height:1.7;max-width:620px;margin-bottom:24px;">
                Découvrez les joueurs qui ont contribué à enrichir la collection en proposant et en créant des cartes.
                Chaque carte est le fruit du travail d'un artiste de la communauté.
            </p>
            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                <div class="stat-block" style="min-width:130px;padding:14px 18px;">
                    <div class="stat-block-icon"><i class="bi bi-brush-fill"></i></div>
                    <div class="stat-block-num"><?= $totalCreators ?></div>
                    <div class="stat-block-label">Artiste<?= $totalCreators > 1 ? 's' : '' ?></div>
                </div>
                <div class="stat-block" style="min-width:130px;padding:14px 18px;">
                    <div class="stat-block-icon"><i class="bi bi-collection-fill"></i></div>
                    <div class="stat-block-num"><?= $totalAuthoredCards ?></div>
                    <div class="stat-block-label">Carte<?= $totalAuthoredCards > 1 ? 's' : '' ?> créées</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($creators)): ?>
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-brush empty-state-icon"></i>
                <div class="empty-state-title">Aucun créateur pour l'instant</div>
                <p class="empty-state-desc">
                    Les artistes apparaissent ici dès que des cartes leur sont attribuées par l'administrateur.
                    <br>Proposez vos créations dans le <a href="<?= appUrl('/community') ?>">forum communautaire</a> !
                </p>
            </div>
        </div>
    <?php else: ?>

        <div class="section-header" style="margin-bottom:20px;">
            <div class="section-title">
                <i class="bi bi-people-fill"></i>
                <?= $totalCreators ?> artiste<?= $totalCreators > 1 ? 's' : '' ?> dans la communauté
            </div>
        </div>

        <div class="creator-grid">
            <?php foreach ($creators as $creator):
                $displayName = $creator['global_name'] ?: $creator['username'];
                $avatarUrl   = getAvatarUrl($creator['id'], $creator['avatar'], 128);
                $bannerUrl   = getBannerUrl($creator['id'], $creator['banner']);

                $previewImages  = $creator['preview_images']  ? explode('|||', $creator['preview_images'])  : [];
                $previewRarities = $creator['preview_rarities'] ? explode('|||', $creator['preview_rarities']) : [];
                $previewNames   = $creator['preview_names']   ? explode('|||', $creator['preview_names'])   : [];

                // Max 3 previews
                $previews = array_slice($previewImages, 0, 3);
                $previewRarSliced = array_slice($previewRarities, 0, 3);
            ?>
                <a class="creator-card" href="<?= appUrl('/user.php?id=' . urlencode($creator['id'])) ?>"
                   title="<?= h($displayName) ?>">

                    <!-- Banner -->
                    <div class="creator-card-banner <?= $bannerUrl ? '' : 'creator-card-banner-default' ?>"
                         <?= $bannerUrl ? 'style="background-image:url(\'' . h($bannerUrl) . '\')"' : '' ?>>
                    </div>

                    <!-- Body -->
                    <div class="creator-card-body">
                        <img class="creator-card-avatar"
                             src="<?= h($avatarUrl) ?>"
                             alt="<?= h($displayName) ?>"
                             onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">

                        <div class="creator-card-name"><?= h($displayName) ?></div>
                        <div class="creator-card-handle">@<?= h($creator['username']) ?></div>

                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="creator-card-stat">
                                <i class="bi bi-collection-fill"></i>
                                <?= (int)$creator['cards_count'] ?> carte<?= $creator['cards_count'] > 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <?php if (!empty($previews)): ?>
                            <div class="creator-card-previews">
                                <?php foreach ($previews as $i => $img): ?>
                                    <?php if ($img): ?>
                                        <div class="creator-card-preview-item rarity-<?= h($previewRarSliced[$i] ?? 'commune') ?>"
                                             style="position:relative;">
                                            <img src="<?= h(cardImageUrl($img)) ?>"
                                                 alt="Carte"
                                                 loading="lazy"
                                                 style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);"
                                                 onerror="this.parentElement.style.display='none'">
                                        </div>
                                    <?php else: ?>
                                        <div class="creator-card-preview-item creator-card-preview-empty">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php
                                $remaining = (int)$creator['cards_count'] - count($previews);
                                if ($remaining > 0):
                                ?>
                                    <div class="creator-card-preview-item creator-card-preview-empty"
                                         style="font-size:13px;font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--text-4);">
                                        +<?= $remaining ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Call to action community -->
        <div style="margin-top:40px;padding:28px;background:var(--bg-400);border:1px solid var(--b-1);border-radius:var(--radius-xl);text-align:center;">
            <i class="bi bi-brush-fill" style="font-size:32px;color:var(--gold-3);display:block;margin-bottom:12px;"></i>
            <h3 style="font-size:18px;margin-bottom:8px;">Vous êtes artiste ?</h3>
            <p style="color:var(--text-4);font-size:14px;max-width:480px;margin:0 auto 20px;line-height:1.65;">
                Proposez vos créations dans le forum communautaire. Les meilleures propositions
                sont intégrées à la collection officielle et vous y serez crédité en tant qu'auteur.
            </p>
            <a href="<?= appUrl('/community') ?>" class="btn-gold">
                <i class="bi bi-send-fill"></i> Proposer une carte
            </a>
        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
define('PAGE_TITLE', 'Forum communautaire');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$values = [
    'name' => '',
    'character_name' => '',
    'serie' => 'Fullmetal Alchemist',
    'rarity' => '',
    'description' => '',
];
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();

    $values['name'] = trim($_POST['name'] ?? '');
    $values['character_name'] = trim($_POST['character_name'] ?? '');
    $values['serie'] = trim($_POST['serie'] ?? 'Fullmetal Alchemist');
    $values['rarity'] = $_POST['rarity'] ?? '';
    $values['description'] = trim($_POST['description'] ?? '');

    if (!$values['name']) $errors[] = 'Le nom de la carte est obligatoire.';
    if (!$values['character_name']) $errors[] = 'Le personnage est obligatoire.';
    if ($values['rarity'] && !array_key_exists($values['rarity'], RARITIES)) $errors[] = 'Rareté invalide.';
    if (!$values['description']) $errors[] = 'Décrivez un minimum votre proposition.';

    $imageFile = saveProposalImage($_FILES['image'] ?? [], $errors);

    if (empty($errors)) {
        $id = dbExecute(
            'INSERT INTO card_proposals (user_id, name, character_name, serie, rarity, description, image_file)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $_SESSION['user_id'],
                $values['name'],
                $values['character_name'],
                $values['serie'] ?: 'Fullmetal Alchemist',
                $values['rarity'] ?: null,
                $values['description'],
                $imageFile,
            ]
        );
        $imageUrl = $imageFile ? proposalImageUrl($imageFile) : null;
        enqueueActivityEvent(
            'community_post',
            $_SESSION['user_id'],
            'Nouvelle proposition communautaire',
            ($_SESSION['global_name'] ?? $_SESSION['username']) . ' propose une nouvelle carte : ' . $values['name'] . '.',
            APP_URL . '/proposal.php?id=' . $id,
            [
                'proposal_id' => $id,
                'image_url' => $imageUrl,
                'image_file' => $imageFile,
                'description' => clipReportText($values['description'], 1800),
            ]
        );
        header('Location: ' . APP_URL . '/proposal.php?id=' . $id);
        exit;
    }
}

$where = ['1=1'];
$params = [];
if (in_array($status, ['open', 'accepted', 'rejected'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.character_name LIKE ? OR p.description LIKE ?)';
    $q = '%' . $search . '%';
    array_push($params, $q, $q, $q);
}
$whereSql = implode(' AND ', $where);

$communityStats = dbQueryOne(
    'SELECT
        COUNT(*) as total,
        SUM(status = "open") as open_count,
        SUM(status = "accepted") as accepted_count,
        SUM(status = "rejected") as rejected_count
     FROM card_proposals'
) ?: ['total' => 0, 'open_count' => 0, 'accepted_count' => 0, 'rejected_count' => 0];

$topProposal = dbQueryOne(
    'SELECT p.*, COUNT(l.user_id) as likes_count
     FROM card_proposals p
     LEFT JOIN card_proposal_likes l ON l.proposal_id = p.id
     GROUP BY p.id
     ORDER BY likes_count DESC, p.created_at DESC
     LIMIT 1'
);

$proposals = dbQuery(
    "SELECT p.*, u.username, u.global_name, u.avatar,
            COUNT(DISTINCT l.user_id) as likes_count,
            COUNT(DISTINCT r.id) as replies_count
     FROM card_proposals p
     JOIN users u ON u.id = p.user_id
     LEFT JOIN card_proposal_likes l ON l.proposal_id = p.id
     LEFT JOIN card_proposal_replies r ON r.proposal_id = p.id
     WHERE $whereSql
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT 40",
    $params
);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">
    <section class="community-hero">
        <div>
            <div class="community-kicker"><i class="bi bi-chat-square-heart-fill"></i> Atelier communautaire</div>
            <h1>Propositions de cartes</h1>
            <p>Publiez vos idées, ajoutez une image au format carte, votez pour les meilleures propositions et échangez avec les autres joueurs.</p>
        </div>
        <div class="community-hero-stats">
            <div><strong><?= (int)$communityStats['total'] ?></strong><span>Total</span></div>
            <div><strong><?= (int)$communityStats['open_count'] ?></strong><span>Ouvertes</span></div>
            <div><strong><?= (int)$communityStats['accepted_count'] ?></strong><span>Acceptées</span></div>
        </div>
    </section>

    <form method="GET" class="community-toolbar">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input class="search-input" name="search" value="<?= h($search) ?>" placeholder="Rechercher une carte, un personnage, une idée...">
        </div>
        <div class="toolbar-sep"></div>
        <?php foreach (['all' => 'Toutes', 'open' => 'Ouvertes', 'accepted' => 'Acceptées', 'rejected' => 'Refusées'] as $key => $label): ?>
            <a class="filter-chip <?= $status === $key ? 'active' : '' ?>" href="?status=<?= urlencode($key) ?>&search=<?= urlencode($search) ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
        <button class="btn-secondary btn-sm" type="submit"><i class="bi bi-funnel-fill"></i> Filtrer</button>
    </form>

    <div class="community-layout">
        <section>
            <?php if ($topProposal): ?>
                <a class="community-featured" href="<?= APP_URL ?>/proposal.php?id=<?= (int)$topProposal['id'] ?>">
                    <div>
                        <span class="community-kicker"><i class="bi bi-fire"></i> Proposition la plus aimée</span>
                        <h2><?= h($topProposal['name']) ?></h2>
                        <p><?= h(textExcerpt($topProposal['description'] ?? '', 120)) ?></p>
                    </div>
                    <strong><i class="bi bi-heart-fill"></i> <?= (int)$topProposal['likes_count'] ?></strong>
                </a>
            <?php endif; ?>

            <?php if (empty($proposals)): ?>
                <div class="panel">
                    <div class="empty-state">
                        <i class="bi bi-chat-square-heart empty-state-icon"></i>
                        <div class="empty-state-title">Aucune proposition</div>
                        <p class="empty-state-desc">Soyez le premier à proposer une carte.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="proposal-list">
                    <?php foreach ($proposals as $proposal): ?>
                        <a class="proposal-card <?= $proposal['image_file'] ? 'has-image' : '' ?>" href="<?= APP_URL ?>/proposal.php?id=<?= (int)$proposal['id'] ?>">
                            <div class="proposal-thumb-wrap">
                                <?php if ($proposal['image_file']): ?>
                                    <img class="proposal-thumb" src="<?= h(proposalImageUrl($proposal['image_file'])) ?>" alt="<?= h($proposal['name']) ?>">
                                <?php else: ?>
                                    <div class="proposal-thumb proposal-thumb-empty"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <?php if ($proposal['rarity']): ?>
                                    <span class="rarity-badge <?= h($proposal['rarity']) ?>"><?= h(rarityLabel($proposal['rarity'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="proposal-main">
                                <div class="proposal-title-row">
                                    <h2><?= h($proposal['name']) ?></h2>
                                    <span class="proposal-status <?= h($proposal['status']) ?>"><?= h($proposal['status']) ?></span>
                                </div>
                                <div class="proposal-meta">
                                    <img src="<?= h(getAvatarUrl($proposal['user_id'], $proposal['avatar'], 64)) ?>" alt="" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                                    <span><?= h($proposal['global_name'] ?: $proposal['username']) ?></span>
                                    <span><?= date('d/m/Y', strtotime($proposal['created_at'])) ?></span>
                                </div>
                                <div class="proposal-character"><?= h($proposal['character_name']) ?> · <?= h($proposal['serie']) ?></div>
                                <p><?= h(textExcerpt($proposal['description'] ?? '', 160)) ?></p>
                                <div class="proposal-stats">
                                    <span><i class="bi bi-heart-fill"></i> <?= (int)$proposal['likes_count'] ?></span>
                                    <span><i class="bi bi-chat-fill"></i> <?= (int)$proposal['replies_count'] ?></span>
                                    <span><i class="bi bi-arrow-right"></i> Ouvrir</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="panel community-compose">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-plus-circle-fill"></i> Proposer une carte</div>
            </div>
            <?php if (!isLoggedIn()): ?>
                <div class="empty-state" style="padding:24px;">
                    <i class="bi bi-discord empty-state-icon"></i>
                    <div class="empty-state-title">Connexion requise</div>
                    <p class="empty-state-desc">Connectez-vous pour proposer une carte et répondre.</p>
                    <a href="<?= APP_URL ?>/login.php" class="btn-primary"><i class="bi bi-discord"></i> Connexion</a>
                </div>
            <?php else: ?>
                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-error" style="margin:0 16px 10px;"><i class="bi bi-x-circle-fill"></i><?= h($e) ?></div>
                <?php endforeach; ?>
                <form method="POST" enctype="multipart/form-data" class="community-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="form-group">
                        <label class="form-label">Nom de la carte</label>
                        <input name="name" class="form-control" value="<?= h($values['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Personnage</label>
                        <input name="character_name" class="form-control" value="<?= h($values['character_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Série</label>
                        <input name="serie" class="form-control" value="<?= h($values['serie']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rareté proposée</label>
                        <select name="rarity" class="form-control">
                            <option value="">Aucune préférence</option>
                            <?php foreach (RARITIES as $key => $r): ?>
                                <option value="<?= $key ?>" <?= $values['rarity'] === $key ? 'selected' : '' ?>><?= h($r['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5" required placeholder="Décrivez la scène, le style, ou mentionnez quelqu'un avec @pseudo."><?= h($values['description']) ?></textarea>
                    </div>
                    <div class="community-image-field">
                        <div class="community-image-spec">
                            <div class="community-card-ratio">
                                <span>2:3</span>
                            </div>
                            <div>
                                <strong>Image optionnelle</strong>
                                <p>Format carte vertical exact. Recommandé : 1000 x 1500 px. Les propositions avec image sont plus faciles à évaluer.</p>
                            </div>
                        </div>
                    <label class="upload-zone community-upload">
                        <input type="file" name="image" accept="image/*">
                        <i class="bi bi-image-fill upload-zone-icon"></i>
                        <span class="upload-zone-text">Déposer ou choisir une image</span>
                        <span class="upload-zone-hint">JPG, PNG, GIF, WEBP · max 5 Mo</span>
                    </label>
                    <div class="upload-preview"></div>
                    </div>
                    <button class="btn-gold w-full" type="submit"><i class="bi bi-send-fill"></i> Publier</button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

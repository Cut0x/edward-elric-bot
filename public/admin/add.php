<?php
define('PAGE_TITLE', 'Admin — Ajouter une carte');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$errors = [];
$authors = isOwner()
    ? dbQuery('SELECT id, username, global_name FROM users ORDER BY COALESCE(global_name, username), username')
    : [];
$values = [
    'name'           => '',
    'character_name' => '',
    'description'    => '',
    'serie'          => 'Fullmetal Alchemist',
    'rarity'         => 'commune',
    'author_id'      => '',
    'is_active'      => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $values['name']           = trim($_POST['name'] ?? '');
    $values['character_name'] = trim($_POST['character_name'] ?? '');
    $values['description']    = trim($_POST['description'] ?? '');
    $values['serie']          = trim($_POST['serie'] ?? 'Fullmetal Alchemist');
    $values['rarity']         = $_POST['rarity'] ?? 'commune';
    $values['author_id']      = isOwner() ? preg_replace('/\D+/', '', $_POST['author_id'] ?? '') : '';
    $values['is_active']      = isset($_POST['is_active']) ? '1' : '0';
    $authorId                 = $values['author_id'] ?: $_SESSION['user_id'];

    if (!$values['name'])           $errors[] = 'Le nom est obligatoire.';
    if (!$values['character_name']) $errors[] = 'Le nom du personnage est obligatoire.';
    if (!array_key_exists($values['rarity'], RARITIES)) $errors[] = 'Rareté invalide.';

    $imageFile = '';
    if (!empty($_FILES['image']['name'])) {
        $file     = $_FILES['image'];
        $allowed  = ['jpg','jpeg','png','gif','webp'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxSize  = 5 * 1024 * 1024;

        if (!in_array($ext, $allowed, true)) $errors[] = 'Format d\'image non supporté (jpg, png, gif, webp).';
        if ($file['size'] > $maxSize)        $errors[] = 'Image trop lourde (max 5 Mo).';
        if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Erreur lors de l\'upload.';

        if (empty($errors)) {
            if (!is_dir(CARDS_DIR)) mkdir(CARDS_DIR, 0755, true);
            $slug      = preg_replace('/[^a-z0-9]+/', '-', strtolower($values['name']));
            $imageFile = $slug . '-' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], CARDS_DIR . $imageFile)) {
                $errors[] = 'Impossible de sauvegarder l\'image.';
                $imageFile = '';
            }
        }
    } else {
        $errors[] = 'Une image est obligatoire.';
    }

    if (empty($errors)) {
        $weight = RARITIES[$values['rarity']]['weight'];
        dbExecute(
            'INSERT INTO cards (name, character_name, description, serie, rarity, rarity_weight, image_file, author_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$values['name'], $values['character_name'], $values['description'],
             $values['serie'], $values['rarity'], $weight, $imageFile, $authorId, $values['is_active']]
        );
        $_SESSION['admin_success'] = 'Carte « ' . $values['name'] . ' » créée avec succès !';
        header('Location: ' . APP_URL . '/admin/cards.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-section">Principal</div>
        <?php if (isOwner()): ?>
            <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-item"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/admin/cards.php" class="admin-nav-item"><i class="bi bi-collection-fill"></i> Cartes</a>
        <a href="<?= APP_URL ?>/admin/add.php" class="admin-nav-item active"><i class="bi bi-plus-circle-fill"></i> Ajouter</a>
        <?php if (isOwner()): ?>
            <a href="<?= APP_URL ?>/admin/users.php" class="admin-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
        <?php endif; ?>
        <div class="admin-sidebar-section">Accès rapide</div>
        <a href="<?= APP_URL ?>/cards.php" class="admin-nav-item" target="_blank"><i class="bi bi-eye-fill"></i> Voir le site</a>
        <a href="<?= APP_URL ?>/index.php" class="admin-nav-item"><i class="bi bi-arrow-left"></i> Retour</a>
    </aside>

    <main class="admin-content">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Ajouter une carte</h1>
                <p class="admin-page-subtitle">Créez la carte, associez son auteur et ajoutez l'image qui sera utilisée par le roll.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/cards.php" class="btn-secondary btn-sm"><i class="bi bi-collection-fill"></i> Cartes</a>
        </div>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error" style="margin-bottom:10px;"><i class="bi bi-x-circle-fill"></i><?= h($e) ?></div>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="admin-card-form">
                <section class="panel admin-form-panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="bi bi-pencil-square"></i> Informations</div>
                    </div>

                    <div class="admin-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="name">Nom de la carte</label>
                            <input id="name" type="text" name="name" class="form-control" value="<?= h($values['name']) ?>" required placeholder="Edward L'Alchimiste">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="character_name">Personnage</label>
                            <input id="character_name" type="text" name="character_name" class="form-control" value="<?= h($values['character_name']) ?>" required placeholder="Edward Elric">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="serie">Série</label>
                            <input id="serie" type="text" name="serie" class="form-control" value="<?= h($values['serie']) ?>" placeholder="Fullmetal Alchemist">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rarity">Rareté</label>
                            <select id="rarity" name="rarity" class="form-control">
                                <?php foreach (RARITIES as $key => $r): ?>
                                    <option value="<?= $key ?>" <?= $values['rarity'] === $key ? 'selected' : '' ?>>
                                        <?= h($r['label']) ?> · <?= round(($r['weight'] / array_sum(array_column(RARITIES, 'weight'))) * 100, 1) ?>%
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (isOwner()): ?>
                            <div class="form-group admin-form-wide">
                                <label class="form-label" for="author_id">Auteur</label>
                                <select id="author_id" name="author_id" class="form-control">
                                    <option value="">Moi-même</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?= h($author['id']) ?>" <?= $values['author_id'] === (string)$author['id'] ? 'selected' : '' ?>>
                                            <?= h($author['global_name'] ?: $author['username']) ?> (@<?= h($author['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Optionnel. Si rien n'est choisi, vous serez défini comme auteur.</div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group admin-form-wide">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="5" placeholder="Texte affiché sur la fiche de la carte."><?= h($values['description']) ?></textarea>
                        </div>
                    </div>
                </section>

                <aside class="panel admin-form-panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="bi bi-image-fill"></i> Image et statut</div>
                    </div>

                    <label class="upload-zone admin-upload-zone">
                        <input type="file" name="image" accept="image/*" required>
                        <i class="bi bi-cloud-arrow-up-fill upload-zone-icon"></i>
                        <span class="upload-zone-text">Déposez une image ou cliquez</span>
                        <span class="upload-zone-hint">Format exact carte : ratio 2:3, recommandé 1000 x 1500 px · JPG, PNG, GIF, WEBP · max 5 Mo</span>
                    </label>
                    <div class="upload-preview"></div>

                    <label class="checkbox-wrap admin-active-toggle">
                        <input type="checkbox" name="is_active" <?= $values['is_active'] ? 'checked' : '' ?>>
                        <span>
                            <strong>Carte active</strong>
                            <small>Disponible dans `/roll` et visible sur le site.</small>
                        </span>
                    </label>

                    <div class="admin-rarity-preview">
                        <?php foreach (RARITIES as $key => $r):
                            $pct = round(($r['weight'] / array_sum(array_column(RARITIES, 'weight'))) * 100, 1);
                        ?>
                            <div class="admin-rarity-row">
                                <span class="rarity-badge <?= h($key) ?>"><?= h($r['label']) ?></span>
                                <span><?= $pct ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn-gold"><i class="bi bi-plus-lg"></i> Créer</button>
                        <a href="<?= APP_URL ?>/admin/cards.php" class="btn-secondary">Annuler</a>
                    </div>
                </aside>
            </div>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

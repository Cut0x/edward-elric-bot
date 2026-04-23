<?php
define('PAGE_TITLE', 'Admin — Cartes');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$search  = trim($_GET['search'] ?? '');
$rarity  = $_GET['rarity'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = ['1=1'];
$params = [];
if (!isOwner()) {
    $where[] = 'c.author_id = ?';
    $params[] = $_SESSION['user_id'];
}
if ($search) {
    $where[]  = '(c.name LIKE ? OR c.character_name LIKE ?)';
    $s        = "%$search%";
    $params[] = $s; $params[] = $s;
}
if ($rarity) { $where[] = 'c.rarity = ?'; $params[] = $rarity; }

$whereStr = implode(' AND ', $where);
$total    = (int)(dbQueryOne("SELECT COUNT(*) as c FROM cards c WHERE $whereStr", $params)['c'] ?? 0);
$offset   = ($page - 1) * $perPage;
$cards    = dbQuery("SELECT c.*, u.username as author_username, u.global_name as author_name
                     FROM cards c
                     LEFT JOIN users u ON u.id = c.author_id
                     WHERE $whereStr
                     ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
                    array_merge($params, [$perPage, $offset]));
$pages    = (int)ceil($total / $perPage);

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg   = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-section">Principal</div>
        <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-item"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="<?= APP_URL ?>/admin/cards.php" class="admin-nav-item active"><i class="bi bi-collection-fill"></i> Cartes</a>
        <a href="<?= APP_URL ?>/admin/add.php"   class="admin-nav-item"><i class="bi bi-plus-circle-fill"></i> Ajouter</a>
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
                <h1 class="admin-page-title">Gestion des cartes</h1>
                <p class="admin-page-subtitle"><?= $total ?> carte<?= $total>1?'s':'' ?> <?= isOwner() ? 'au total' : 'créée' . ($total>1?'s':'') . ' par vous' ?></p>
            </div>
            <a href="<?= APP_URL ?>/admin/add.php" class="btn-gold btn-sm">
                <i class="bi bi-plus-lg"></i> Nouvelle carte
            </a>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success auto-dismiss"><i class="bi bi-check-circle-fill"></i><?= h($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error auto-dismiss"><i class="bi bi-x-circle-fill"></i><?= h($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">
            <div class="search-wrap" style="min-width:220px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="search-input" placeholder="Rechercher…" value="<?= h($search) ?>">
            </div>
            <select name="rarity" class="form-control" style="width:160px;">
                <option value="">Toutes raretés</option>
                <?php foreach (RARITIES as $k => $r): ?>
                    <option value="<?= $k ?>" <?= $rarity === $k ? 'selected' : '' ?>><?= $r['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-secondary btn-sm"><i class="bi bi-funnel-fill"></i> Filtrer</button>
        </form>

        <!-- Table -->
        <?php if (empty($cards)): ?>
            <div class="panel">
                <div class="empty-state">
                    <i class="bi bi-collection empty-state-icon"></i>
                    <div class="empty-state-title">Aucune carte trouvée</div>
                    <p class="empty-state-desc">Commencez par ajouter des cartes à la collection.</p>
                    <a href="<?= APP_URL ?>/admin/add.php" class="btn-gold btn-sm">
                        <i class="bi bi-plus-lg"></i> Ajouter une carte
                    </a>
                </div>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th style="width:52px;">Image</th>
                        <th>Nom</th>
                        <th>Personnage</th>
                        <th>Rareté</th>
                        <th>Auteur</th>
                        <th>Série</th>
                        <th style="width:72px;">Actif</th>
                        <th style="width:90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cards as $card): ?>
                    <tr>
                        <td style="color:var(--text-5);font-family:'JetBrains Mono',monospace;font-size:11px;"><?= $card['id'] ?></td>
                        <td>
                            <div style="width:32px;height:42px;border-radius:var(--radius-xs);overflow:hidden;background:var(--bg-300);">
                                <?php if ($card['image_file']): ?>
                                    <img class="card-thumbnail"
                                         src="<?= h(cardImageUrl($card['image_file'])) ?>"
                                         style="width:100%;height:100%;object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="font-weight:600;font-size:13px;"><?= h($card['name']) ?></td>
                        <td style="font-size:13px;color:var(--text-3);"><?= h($card['character_name']) ?></td>
                        <td><span class="rarity-badge <?= h($card['rarity']) ?>"><?= rarityLabel($card['rarity']) ?></span></td>
                        <td style="font-size:12px;color:var(--text-4);"><?= h($card['author_name'] ?: ($card['author_username'] ?? 'Inconnu')) ?></td>
                        <td style="font-size:12px;color:var(--text-4);"><?= h($card['serie']) ?></td>
                        <td>
                            <i class="bi bi-<?= $card['is_active'] ? 'check-circle-fill" style="color:var(--green)' : 'x-circle-fill" style="color:var(--red)' ?>"></i>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $card['id'] ?>"
                                   class="btn-outline-sm" title="Modifier" style="padding:4px 8px;">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="<?= APP_URL ?>/admin/delete.php?id=<?= $card['id'] ?>&csrf=<?= csrfToken() ?>"
                                   class="btn-danger" title="Supprimer"
                                   data-confirm="Supprimer « <?= addslashes($card['name']) ?> » définitivement ?">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rarity=<?= urlencode($rarity) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

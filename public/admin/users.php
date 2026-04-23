<?php
define('PAGE_TITLE', 'Admin — Utilisateurs');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireOwner();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $targetId = preg_replace('/\D+/', '', $_POST['user_id'] ?? '');
    if (!$targetId) {
        $errorMsg = 'Utilisateur invalide.';
    } elseif ((string)$targetId === (string)$_SESSION['user_id'] && empty($_POST['is_owner'])) {
        $errorMsg = 'Vous ne pouvez pas vous retirer votre propre rôle owner.';
    } else {
        dbExecute(
            'UPDATE users SET is_owner = ?, is_card_author = ? WHERE id = ?',
            [isset($_POST['is_owner']) ? 1 : 0, isset($_POST['is_card_author']) ? 1 : 0, $targetId]
        );
        $successMsg = 'Rôles mis à jour.';
    }
}

$users = dbQuery(
    'SELECT u.*, COUNT(DISTINCT c.id) as authored_cards, COUNT(DISTINCT uc.card_id) as owned_cards
     FROM users u
     LEFT JOIN cards c ON c.author_id = u.id
     LEFT JOIN user_cards uc ON uc.user_id = u.id
     GROUP BY u.id
     ORDER BY u.is_owner DESC, u.is_card_author DESC, COALESCE(u.global_name, u.username)'
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-section">Principal</div>
        <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-item"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="<?= APP_URL ?>/admin/cards.php" class="admin-nav-item"><i class="bi bi-collection-fill"></i> Cartes</a>
        <a href="<?= APP_URL ?>/admin/add.php" class="admin-nav-item"><i class="bi bi-plus-circle-fill"></i> Ajouter</a>
        <a href="<?= APP_URL ?>/admin/users.php" class="admin-nav-item active"><i class="bi bi-people-fill"></i> Utilisateurs</a>
    </aside>

    <main class="admin-content">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Utilisateurs et rôles</h1>
                <p class="admin-page-subtitle">Seul l'owner peut modifier ces accès.</p>
            </div>
        </div>

        <?php if ($successMsg): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill"></i><?= h($successMsg) ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><i class="bi bi-x-circle-fill"></i><?= h($errorMsg) ?></div><?php endif; ?>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Badges</th>
                        <th>Cartes créées</th>
                        <th>Cartes obtenues</th>
                        <th style="width:250px;">Rôles</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user):
                    $badges = userBadges($user, (int)$user['owned_cards']);
                ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="<?= h(getAvatarUrl($user['id'], $user['avatar'], 64)) ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover;" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                                <div>
                                    <div style="font-weight:700;color:var(--text-1);"><?= h(userDisplayName($user)) ?></div>
                                    <div style="font-size:11px;color:var(--text-5);">@<?= h($user['username']) ?> · <?= h($user['id']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="profile-badges" style="margin-top:0;">
                                <?php foreach ($badges as $badge): ?>
                                    <span class="profile-badge-tip" data-tooltip="<?= h($badge['label']) ?>">
                                        <img class="profile-badge" src="<?= h($badge['image']) ?>" alt="<?= h($badge['label']) ?>">
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><?= (int)$user['authored_cards'] ?></td>
                        <td><?= (int)$user['owned_cards'] ?></td>
                        <td>
                            <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="user_id" value="<?= h($user['id']) ?>">
                                <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-3);">
                                    <input type="checkbox" name="is_owner" <?= !empty($user['is_owner']) ? 'checked' : '' ?>> Owner
                                </label>
                                <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-3);">
                                    <input type="checkbox" name="is_card_author" <?= !empty($user['is_card_author']) ? 'checked' : '' ?>> Auteur
                                </label>
                                <button type="submit" class="btn-secondary btn-sm"><i class="bi bi-save-fill"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

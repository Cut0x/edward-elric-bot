<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$proposal = dbQueryOne(
    'SELECT p.*, u.username, u.global_name, u.avatar
     FROM card_proposals p
     JOIN users u ON u.id = p.user_id
     WHERE p.id = ?',
    [$id]
);

if (!$proposal) {
    header('Location: ' . APP_URL . '/community.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'like') {
        $liked = dbQueryOne('SELECT 1 FROM card_proposal_likes WHERE proposal_id = ? AND user_id = ?', [$id, $_SESSION['user_id']]);
        if ($liked) {
            dbExecute('DELETE FROM card_proposal_likes WHERE proposal_id = ? AND user_id = ?', [$id, $_SESSION['user_id']]);
        } else {
            dbExecute('INSERT INTO card_proposal_likes (proposal_id, user_id) VALUES (?, ?)', [$id, $_SESSION['user_id']]);
        }
    }

    if ($action === 'reply') {
        $content = trim($_POST['content'] ?? '');
        if ($content !== '') {
            dbExecute('INSERT INTO card_proposal_replies (proposal_id, user_id, content) VALUES (?, ?, ?)', [$id, $_SESSION['user_id'], $content]);
        }
    }

    header('Location: ' . APP_URL . '/proposal.php?id=' . $id);
    exit;
}

$likes = (int)(dbQueryOne('SELECT COUNT(*) as c FROM card_proposal_likes WHERE proposal_id = ?', [$id])['c'] ?? 0);
$likedByMe = isLoggedIn() ? (bool)dbQueryOne('SELECT 1 FROM card_proposal_likes WHERE proposal_id = ? AND user_id = ?', [$id, $_SESSION['user_id']]) : false;
$replies = dbQuery(
    'SELECT r.*, u.username, u.global_name, u.avatar
     FROM card_proposal_replies r
     JOIN users u ON u.id = r.user_id
     WHERE r.proposal_id = ?
     ORDER BY r.created_at ASC',
    [$id]
);

define('PAGE_TITLE', $proposal['name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">
    <a href="<?= APP_URL ?>/community.php" class="btn-secondary btn-sm mb-24"><i class="bi bi-arrow-left"></i> Forum</a>

    <div class="proposal-detail-layout">
        <aside>
            <div class="proposal-detail-image">
                <?php if ($proposal['image_file']): ?>
                    <img src="<?= h(proposalImageUrl($proposal['image_file'])) ?>" alt="<?= h($proposal['name']) ?>">
                <?php else: ?>
                    <div class="proposal-thumb-empty"><i class="bi bi-image"></i><span>Aucune image</span></div>
                <?php endif; ?>
            </div>
        </aside>

        <main class="panel proposal-thread">
            <div class="proposal-thread-head">
                <span class="proposal-status <?= h($proposal['status']) ?>"><?= h($proposal['status']) ?></span>
                <?php if ($proposal['rarity']): ?><span class="rarity-badge <?= h($proposal['rarity']) ?>"><?= h(rarityLabel($proposal['rarity'])) ?></span><?php endif; ?>
                <h1><?= h($proposal['name']) ?></h1>
                <p><?= h($proposal['character_name']) ?> · <?= h($proposal['serie']) ?></p>
                <div class="proposal-meta">
                    <img src="<?= h(getAvatarUrl($proposal['user_id'], $proposal['avatar'], 64)) ?>" alt="" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                    <a href="<?= APP_URL ?>/user.php?id=<?= urlencode($proposal['user_id']) ?>"><?= h($proposal['global_name'] ?: $proposal['username']) ?></a>
                    <span><?= date('d/m/Y H:i', strtotime($proposal['created_at'])) ?></span>
                </div>
            </div>

            <div class="proposal-body"><?= renderForumText($proposal['description']) ?></div>

            <div class="proposal-actions">
                <?php if (isLoggedIn()): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="like">
                        <button class="btn-secondary btn-sm" type="submit">
                            <i class="bi bi-heart<?= $likedByMe ? '-fill' : '' ?>"></i> <?= $likes ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="btn-secondary btn-sm" href="<?= APP_URL ?>/login.php"><i class="bi bi-heart"></i> <?= $likes ?></a>
                <?php endif; ?>
                <span class="proposal-action-count"><i class="bi bi-chat-fill"></i> <?= count($replies) ?> réponses</span>
            </div>

            <div class="proposal-replies">
                <?php foreach ($replies as $reply): ?>
                    <article class="proposal-reply">
                        <img src="<?= h(getAvatarUrl($reply['user_id'], $reply['avatar'], 64)) ?>" alt="" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                        <div>
                            <div class="proposal-reply-head">
                                <a href="<?= APP_URL ?>/user.php?id=<?= urlencode($reply['user_id']) ?>"><?= h($reply['global_name'] ?: $reply['username']) ?></a>
                                <span><?= date('d/m/Y H:i', strtotime($reply['created_at'])) ?></span>
                            </div>
                            <div class="proposal-reply-text"><?= renderForumText($reply['content']) ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (isLoggedIn()): ?>
                <form method="POST" class="proposal-reply-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="reply">
                    <textarea name="content" class="form-control" rows="3" required placeholder="Répondre, mentionner avec @pseudo..."></textarea>
                    <button class="btn-gold" type="submit"><i class="bi bi-reply-fill"></i> Répondre</button>
                </form>
            <?php else: ?>
                <div class="panel" style="padding:14px;"><a href="<?= APP_URL ?>/login.php" class="btn-primary"><i class="bi bi-discord"></i> Connexion pour répondre</a></div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

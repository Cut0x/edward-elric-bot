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
        if ($proposal['status'] !== 'open') {
            header('Location: ' . APP_URL . '/proposal.php?id=' . $id);
            exit;
        }
        $content = trim($_POST['content'] ?? '');
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $parentId = $parentId > 0 ? $parentId : null;

        if ($parentId) {
            $parent = dbQueryOne(
                'SELECT id FROM card_proposal_replies WHERE id = ? AND proposal_id = ?',
                [$parentId, $id]
            );
            if (!$parent) $parentId = null;
        }

        if ($content !== '') {
            $replyId = dbExecute(
                'INSERT INTO card_proposal_replies (proposal_id, parent_id, user_id, content) VALUES (?, ?, ?, ?)',
                [$id, $parentId, $_SESSION['user_id'], $content]
            );
            enqueueActivityEvent(
                'community_reply',
                $_SESSION['user_id'],
                'Nouvelle réponse communautaire',
                ($_SESSION['global_name'] ?? $_SESSION['username']) . ' a répondu à la proposition : ' . $proposal['name'] . '.',
                APP_URL . '/proposal.php?id=' . $id . '#reply-' . $replyId,
                [
                    'proposal_id' => $id,
                    'reply_id' => $replyId,
                    'parent_id' => $parentId,
                    'reply_content' => clipReportText($content, 1800),
                ]
            );
        }
    }

    if ($action === 'report_proposal') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason !== '') {
            enqueueActivityEvent(
                'report',
                $_SESSION['user_id'],
                'Report de proposition',
                ($_SESSION['global_name'] ?? $_SESSION['username']) . ' a signalé la proposition : ' . $proposal['name'] . '.',
                APP_URL . '/proposal.php?id=' . $id,
                [
                    'target_type' => 'proposal',
                    'target_id' => $id,
                    'reported_user_id' => $proposal['user_id'],
                    'reason' => clipReportText($reason, 800),
                    'reported_content' => clipReportText($proposal['description'], 1600),
                ]
            );
        }
    }

    if ($action === 'report_reply') {
        $replyId = (int)($_POST['reply_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $reply = dbQueryOne('SELECT id, user_id, content FROM card_proposal_replies WHERE id = ? AND proposal_id = ?', [$replyId, $id]);
        if ($reply && $reason !== '') {
            enqueueActivityEvent(
                'report',
                $_SESSION['user_id'],
                'Report de réponse',
                ($_SESSION['global_name'] ?? $_SESSION['username']) . ' a signalé une réponse sur : ' . $proposal['name'] . '.',
                APP_URL . '/proposal.php?id=' . $id . '#reply-' . $replyId,
                [
                    'target_type' => 'reply',
                    'target_id' => $replyId,
                    'proposal_id' => $id,
                    'reported_user_id' => $reply['user_id'],
                    'reason' => clipReportText($reason, 800),
                    'reported_content' => clipReportText($reply['content'], 1600),
                ]
            );
        }
    }

    if ($action === 'accept_as_card' && isAdmin() && $proposal['status'] === 'open') {
        $cardId = createDraftCardFromProposal($proposal);
        dbExecute('UPDATE card_proposals SET status = "accepted" WHERE id = ?', [$id]);
        header('Location: ' . APP_URL . '/admin/edit.php?id=' . $cardId);
        exit;
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
$replyTree = [];
$replyChildren = [];
foreach ($replies as $reply) {
    $reply['children'] = [];
    $parent = $reply['parent_id'] ?? null;
    if ($parent) {
        $replyChildren[(int)$parent][] = $reply;
    } else {
        $replyTree[] = $reply;
    }
}

function renderProposalReply(array $reply, array $childrenByParent, int $proposalId, bool $isOpen, int $depth = 0): void {
    $replyId = (int)$reply['id'];
    ?>
    <article class="proposal-reply" id="reply-<?= $replyId ?>" style="--reply-depth: <?= min($depth, 6) ?>;">
        <img src="<?= h(getAvatarUrl($reply['user_id'], $reply['avatar'], 64)) ?>" alt="" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
        <div>
            <div class="proposal-reply-head">
                <a href="<?= APP_URL ?>/user.php?id=<?= urlencode($reply['user_id']) ?>"><?= h($reply['global_name'] ?: $reply['username']) ?></a>
                <span><?= date('d/m/Y H:i', strtotime($reply['created_at'])) ?></span>
                <a href="#reply-<?= $replyId ?>" class="proposal-reply-anchor">#<?= $replyId ?></a>
            </div>
            <div class="proposal-reply-text"><?= renderForumText($reply['content']) ?></div>

            <div class="proposal-reply-tools">
                <?php if (isLoggedIn()): ?>
                    <details class="proposal-report-details">
                        <summary><i class="bi bi-flag-fill"></i> Signaler</summary>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="report_reply">
                            <input type="hidden" name="reply_id" value="<?= $replyId ?>">
                            <textarea name="reason" class="form-control" rows="2" required minlength="3" maxlength="800" placeholder="Raison du signalement"></textarea>
                            <button class="btn-danger btn-sm" type="submit"><i class="bi bi-flag-fill"></i> Envoyer</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn() && $isOpen): ?>
                <details class="proposal-inline-reply">
                    <summary><i class="bi bi-reply-fill"></i> Répondre</summary>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="parent_id" value="<?= $replyId ?>">
                        <textarea name="content" class="form-control" rows="2" required placeholder="Répondre à <?= h($reply['global_name'] ?: $reply['username']) ?>..."></textarea>
                        <button class="btn-secondary btn-sm" type="submit"><i class="bi bi-send-fill"></i> Envoyer</button>
                    </form>
                </details>
            <?php endif; ?>
        </div>
    </article>

    <?php foreach (($childrenByParent[$replyId] ?? []) as $child): ?>
        <?php renderProposalReply($child, $childrenByParent, $proposalId, $isOpen, $depth + 1); ?>
    <?php endforeach; ?>
    <?php
}

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
                <?php if (isLoggedIn()): ?>
                    <details class="proposal-report-details proposal-report-details-main">
                        <summary><i class="bi bi-flag-fill"></i> Signaler</summary>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="report_proposal">
                            <textarea name="reason" class="form-control" rows="2" required minlength="3" maxlength="800" placeholder="Raison du signalement"></textarea>
                            <button class="btn-danger btn-sm" type="submit"><i class="bi bi-flag-fill"></i> Envoyer</button>
                        </form>
                    </details>
                <?php endif; ?>
                <?php if (isAdmin() && $proposal['status'] === 'open'): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="accept_as_card">
                        <button class="btn-gold btn-sm" type="submit"><i class="bi bi-plus-circle-fill"></i> Ajouter en brouillon</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($proposal['status'] !== 'open'): ?>
                <div class="proposal-closed-notice">
                    <i class="bi bi-lock-fill"></i>
                    Cette proposition est clôturée. Les réponses sont fermées.
                </div>
            <?php endif; ?>

            <div class="proposal-replies">
                <?php foreach ($replyTree as $reply): ?>
                    <?php renderProposalReply($reply, $replyChildren, $id, $proposal['status'] === 'open'); ?>
                <?php endforeach; ?>
            </div>

            <?php if (isLoggedIn() && $proposal['status'] === 'open'): ?>
                <form method="POST" class="proposal-reply-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="parent_id" value="">
                    <textarea name="content" class="form-control" rows="3" required placeholder="Répondre, mentionner avec @pseudo..."></textarea>
                    <button class="btn-gold" type="submit"><i class="bi bi-reply-fill"></i> Répondre</button>
                </form>
            <?php elseif ($proposal['status'] === 'open'): ?>
                <div class="panel" style="padding:14px;"><a href="<?= APP_URL ?>/login.php" class="btn-primary"><i class="bi bi-discord"></i> Connexion pour répondre</a></div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

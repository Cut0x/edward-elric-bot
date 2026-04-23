<?php
define('PAGE_TITLE', 'Classement');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$leaders    = getLeaderboard(50);
$totalCards = (int)(dbQueryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1')['c'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content">

    <div class="section-header mb-24">
        <div>
            <div class="section-title"><i class="bi bi-trophy-fill"></i> Classement</div>
            <div class="section-meta">Les meilleurs collectionneurs parmi <?= count($leaders) ?> joueurs</div>
        </div>
    </div>

    <?php if (empty($leaders)): ?>
        <div class="panel">
            <div class="empty-state">
                <i class="bi bi-trophy empty-state-icon"></i>
                <div class="empty-state-title">Aucun joueur pour l'instant</div>
                <p class="empty-state-desc">
                    Soyez le premier à vous connecter et à utiliser <code class="cmd">/roll</code> pour apparaître ici !
                </p>
                <?php if (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/login.php" class="btn-primary">
                        <i class="bi bi-discord"></i> Se connecter
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>

        <!-- Top 3 -->
        <?php if (count($leaders) >= 3): ?>
        <div class="panel" style="padding:24px;margin-bottom:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;max-width:500px;margin:0 auto;align-items:end;">
                <?php
                $podium = [
                    0 => ['pos' => 1, 'height' => '110px', 'col' => 1, 'color' => '#e3a321'],
                    1 => ['pos' => 2, 'height' => '80px',  'col' => 0, 'color' => '#b5bac1'],
                    2 => ['pos' => 3, 'height' => '60px',  'col' => 2, 'color' => '#b87333'],
                ];
                // Sort by column position
                usort($podium, fn($a,$b) => $a['col'] <=> $b['col']);
                ?>
                <?php foreach ($podium as $p):
                    $u = $leaders[$p['pos'] - 1];
                    $pct = $totalCards > 0 ? round(($u['owned_count'] / $totalCards) * 100, 1) : 0;
                    $xpData = xpInfo((int)($u['xp'] ?? 0));
                ?>
                <a href="<?= APP_URL ?>/user.php?id=<?= urlencode($u['id']) ?>"
                   style="display:block;text-align:center;text-decoration:none;">
                    <img src="<?= h(getAvatarUrl($u['id'], $u['avatar'])) ?>"
                         alt=""
                         style="width:<?= $p['pos'] === 1 ? '56px' : '44px' ?>;height:<?= $p['pos'] === 1 ? '56px' : '44px' ?>;border-radius:50%;border:3px solid <?= $p['color'] ?>;object-fit:cover;margin:0 auto 8px;"
                         onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                    <div style="font-size:12px;font-weight:700;color:var(--text-1);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= h($u['global_name'] ?? $u['username']) ?>
                    </div>
                    <div style="background:var(--bg-500);border-radius:var(--radius-md) var(--radius-md) 0 0;height:<?= $p['height'] ?>;display:flex;flex-direction:column;align-items:center;justify-content:center;border-top:3px solid <?= $p['color'] ?>;">
                        <span style="font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:700;color:<?= $p['color'] ?>;"><?= $p['pos'] === 1 ? '👑' : '#'.$p['pos'] ?></span>
                        <span style="font-size:12px;color:var(--text-3);margin-top:2px;"><?= $u['owned_count'] ?> cartes</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full list -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-list-ol"></i> Classement complet</div>
            </div>

            <?php foreach ($leaders as $i => $user):
                $rank   = $i + 1;
                $isMe   = isLoggedIn() && (string)$user['id'] === (string)$_SESSION['user_id'];
                $pct    = $totalCards > 0 ? round(($user['owned_count'] / $totalCards) * 100, 1) : 0;
                $xpData = xpInfo((int)($user['xp'] ?? 0));
                $lv     = $xpData['level'];
                $lvClass = levelBadgeClass($lv);
            ?>
                <a class="lb-row <?= $isMe ? 'is-me' : '' ?>" href="<?= APP_URL ?>/user.php?id=<?= urlencode($user['id']) ?>" style="text-decoration:none;">
                    <div class="lb-rank <?= $rank <= 3 ? 'rank-'.$rank : '' ?>">
                        <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#'.$rank)) ?>
                    </div>

                    <img class="lb-avatar"
                         src="<?= h(getAvatarUrl($user['id'], $user['avatar'])) ?>"
                         alt=""
                         onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">

                    <div class="lb-info">
                        <div class="lb-name">
                            <?= h($user['global_name'] ?? $user['username']) ?>
                            <?php if ($isMe): ?>
                                <span style="font-size:10px;background:var(--blurple);color:#fff;padding:1px 6px;border-radius:var(--radius-full);margin-left:4px;">Vous</span>
                            <?php endif; ?>
                        </div>
                        <div class="lb-sub">
                            <span class="level-badge <?= $lvClass ?>" style="font-size:9px;padding:1px 5px;">Nv.<?= $lv ?></span>
                            <span><?= levelTitle($lv) ?></span>
                        </div>
                    </div>

                    <div class="lb-score">
                        <div class="lb-score-num">
                            <img src="<?= ICONS_URL ?>/star.png" class="pixel-icon" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:3px;" alt="">
                            <?= $user['owned_count'] ?>
                        </div>
                        <div class="lb-score-sub">
                            <?= $pct ?>% &nbsp;·&nbsp;
                            <img src="<?= ICONS_URL ?>/experience.gif" class="pixel-icon" style="width:10px;height:10px;display:inline-block;vertical-align:middle;" alt="XP">
                            <?= number_format($user['xp'] ?? 0) ?> XP
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bot_api.php';

// ===== CARDS =====

function cardImageUrl(string $imageFile): string {
    return CARDS_URL . '/' . rawurlencode($imageFile);
}

function proposalImageUrl(string $imageFile): string {
    return PROPOSALS_URL . '/' . rawurlencode($imageFile);
}

function clipReportText(string $text, int $length): string {
    return function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
}

function rarityLabel(string $rarity): string {
    return RARITIES[$rarity]['label'] ?? ucfirst($rarity);
}

function rarityColor(string $rarity): string {
    return RARITIES[$rarity]['color'] ?? '#9e9e9e';
}

function userDisplayName(array $user): string {
    return $user['global_name'] ?: ($user['username'] ?? 'Utilisateur');
}

function userBadges(array $user, int $ownedCount): array {
    $badges = [];
    if (!empty($user['is_owner']) || (string)($user['id'] ?? '') === ADMIN_DISCORD_ID) {
        $badges[] = ['label' => 'Owner', 'image' => BADGES_URL . '/owner.png'];
    }
    if (!empty($user['is_card_author'])) {
        $badges[] = ['label' => 'Auteur de carte', 'image' => BADGES_URL . '/dessinateur_trice.png'];
    }

    foreach ([5, 10, 20, 30, 50, 60, 90, 100] as $count) {
        if ($ownedCount >= $count) {
            $badges[] = ['label' => $count . ' cartes', 'image' => BADGES_URL . '/get_' . $count . '_cards.png'];
        }
    }

    return $badges;
}

function renderForumText(?string $text): string {
    $safe = nl2br(h($text ?? ''));
    return preg_replace_callback('/@([A-Za-z0-9_.]{2,32})/u', function ($m) {
        $user = dbQueryOne('SELECT id, username, global_name FROM users WHERE username = ? LIMIT 1', [$m[1]]);
        if (!$user) return '<span class="mention">@' . h($m[1]) . '</span>';
        return '<a class="mention" href="' . APP_URL . '/user.php?id=' . urlencode($user['id']) . '">@' . h($user['global_name'] ?: $user['username']) . '</a>';
    }, $safe);
}

function textExcerpt(?string $text, int $width = 160): string {
    $text = trim((string)$text);
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, '...', 'UTF-8');
    }
    return strlen($text) > $width ? substr($text, 0, max(0, $width - 3)) . '...' : $text;
}

function enqueueActivityEvent(string $type, ?string $userId, string $title, string $message, ?string $url = null, array $metadata = []): void {
    try {
        dbExecute(
            'INSERT INTO activity_events (type, user_id, title, message, url, metadata_json) VALUES (?, ?, ?, ?, ?, ?)',
            [$type, $userId, $title, $message, $url, $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null]
        );
    } catch (Exception) {
        // L'activité ne doit jamais bloquer le site.
    }
}

function xpRewardRange(string $rarity): array {
    return match ($rarity) {
        'legendaire'  => [450, 650],
        'epique'      => [180, 280],
        'rare'        => [90, 150],
        'peu_commune' => [45, 80],
        default       => [20, 40],
    };
}

function randomXpReward(string $rarity, bool $isDuplicate = false): int {
    if ($isDuplicate) return random_int(max(5, (int)(XP_PER_ROLL / 2)), XP_PER_ROLL);
    [$min, $max] = xpRewardRange($rarity);
    return XP_PER_ROLL + random_int($min, $max);
}

function createDraftCardFromProposal(array $proposal): int {
    $rarity = $proposal['rarity'] && array_key_exists($proposal['rarity'], RARITIES) ? $proposal['rarity'] : 'commune';
    $weight = RARITIES[$rarity]['weight'];
    $imageFile = '';

    if (!empty($proposal['image_file']) && file_exists(PROPOSALS_DIR . $proposal['image_file'])) {
        if (!is_dir(CARDS_DIR)) mkdir(CARDS_DIR, 0755, true);
        $ext = strtolower(pathinfo($proposal['image_file'], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($proposal['name']));
        $imageFile = $slug . '-proposal-' . uniqid() . '.' . $ext;
        copy(PROPOSALS_DIR . $proposal['image_file'], CARDS_DIR . $imageFile);
    }

    $cardId = dbExecute(
        'INSERT INTO cards (name, character_name, description, serie, rarity, rarity_weight, image_file, author_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)',
        [
            $proposal['name'],
            $proposal['character_name'],
            $proposal['description'],
            $proposal['serie'],
            $rarity,
            $weight,
            $imageFile,
            $proposal['user_id'],
        ]
    );

    enqueueActivityEvent(
        'card_added',
        $proposal['user_id'] ? (string)$proposal['user_id'] : null,
        'Carte ajoutée en brouillon',
        'La proposition ' . $proposal['name'] . ' a été transformée en carte non publique.',
        APP_URL . '/admin/edit.php?id=' . $cardId,
        [
            'card_id' => $cardId,
            'image_file' => $imageFile,
            'image_url' => $imageFile ? cardImageUrl($imageFile) : null,
            'description' => clipReportText($proposal['description'] ?? '', 1800),
            'is_active' => 0,
        ]
    );

    return $cardId;
}

function saveProposalImage(array $file, array &$errors): ?string {
    if (empty($file['name'])) return null;

    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($ext, $allowed, true)) $errors[] = 'Format image non supporté (jpg, png, gif, webp).';
    if (($file['size'] ?? 0) > $maxSize) $errors[] = 'Image trop lourde (max 5 Mo).';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $errors[] = 'Erreur lors de l\'upload.';

    if (empty($errors) && in_array($ext, ['jpg','jpeg','png','webp'], true)) {
        $size = @getimagesize($file['tmp_name']);
        if ($size) {
            [$w, $h] = $size;
            $ratio = $w > 0 ? $h / $w : 0;
            if ($ratio < 1.45 || $ratio > 1.55) {
                $errors[] = 'L\'image doit respecter le format carte 2:3, idéalement 1000 x 1500 px.';
            }
        }
    }

    if (!empty($errors)) return null;
    if (!is_dir(PROPOSALS_DIR)) mkdir(PROPOSALS_DIR, 0755, true);

    $name = 'proposal-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], PROPOSALS_DIR . $name)) {
        $errors[] = 'Impossible de sauvegarder l\'image.';
        return null;
    }
    return $name;
}

// ===== XP / LEVEL =====

function xpToLevel(int $xp): int {
    return max(1, (int)floor(sqrt(max(0, $xp) / 100)) + 1);
}

function levelToMinXp(int $level): int {
    return (int)(($level - 1) ** 2 * 100);
}

function xpInfo(int $xp): array {
    $level    = xpToLevel($xp);
    $minXp    = levelToMinXp($level);
    $nextXp   = levelToMinXp($level + 1);
    $progress = $xp - $minXp;
    $needed   = $nextXp - $minXp;
    $percent  = $needed > 0 ? min(100, round(($progress / $needed) * 100)) : 100;

    return [
        'level'    => $level,
        'xp'       => $xp,
        'progress' => $progress,
        'needed'   => $needed,
        'percent'  => $percent,
        'title'    => levelTitle($level),
        'color'    => levelColor($level),
    ];
}

function levelTitle(int $level): string {
    return match (true) {
        $level >= 51 => 'Alchimiste Légendaire',
        $level >= 36 => 'Maître des Transmutations',
        $level >= 21 => 'Grand Alchimiste',
        $level >= 11 => "Alchimiste d'État",
        $level >= 6  => 'Alchimiste Apprenti',
        default      => 'Novice',
    };
}

function levelColor(int $level): string {
    return match (true) {
        $level >= 51 => '#e3a321',
        $level >= 36 => '#e74c3c',
        $level >= 21 => '#a070c0',
        $level >= 11 => '#5b9bd5',
        $level >= 6  => '#57ab71',
        default      => '#80848e',
    };
}

// ===== STATS =====

function getStats(): array {
    try {
        $totalCards = (int)(dbQueryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1')['c'] ?? 0);
        $totalUsers = (int)(dbQueryOne('SELECT COUNT(*) as c FROM users')['c'] ?? 0);
        $totalRolls = (int)(dbQueryOne('SELECT COALESCE(SUM(total_rolls),0) as c FROM users')['c'] ?? 0);
    } catch (Exception) {
        return ['total_cards' => 0, 'total_users' => 0, 'total_rolls' => 0];
    }
    return ['total_cards' => $totalCards, 'total_users' => $totalUsers, 'total_rolls' => $totalRolls];
}

function getUserStats(string $userId): array {
    $owned      = (int)(dbQueryOne('SELECT COUNT(*) as c FROM user_cards WHERE user_id = ?', [$userId])['c'] ?? 0);
    $totalCards = (int)(dbQueryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1')['c'] ?? 0);
    $user       = dbQueryOne('SELECT rolls_remaining, last_roll_reset, total_rolls, xp, level FROM users WHERE id = ?', [$userId]);
    $rarest     = dbQueryOne(
        "SELECT c.name, c.rarity FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = ?
         ORDER BY FIELD(c.rarity,'legendaire','epique','rare','peu_commune','commune') LIMIT 1",
        [$userId]
    );

    $today     = date('Y-m-d');
    $rollsLeft = MAX_DAILY_ROLLS;
    if ($user) {
        if ($user['last_roll_reset'] !== $today) {
            dbExecute('UPDATE users SET rolls_remaining = ?, last_roll_reset = ? WHERE id = ?', [MAX_DAILY_ROLLS, $today, $userId]);
        } else {
            $rollsLeft = (int)$user['rolls_remaining'];
        }
    }

    $xp    = (int)($user['xp'] ?? 0);
    $level = xpToLevel($xp);
    $xpData = xpInfo($xp);

    return [
        'owned'       => $owned,
        'total'       => $totalCards,
        'percent'     => $totalCards > 0 ? round(($owned / $totalCards) * 100, 1) : 0,
        'rolls_left'  => $rollsLeft,
        'total_rolls' => (int)($user['total_rolls'] ?? 0),
        'rarest_card' => $rarest ?: null,
        'xp'          => $xp,
        'level'       => $level,
        'xp_info'     => $xpData,
    ];
}

function getLeaderboard(int $limit = 20): array {
    return dbQuery(
        'SELECT u.id, u.username, u.global_name, u.avatar, u.banner, u.level, u.xp, u.is_owner, u.is_card_author,
                COUNT(uc.card_id) as owned_count
         FROM users u
         LEFT JOIN user_cards uc ON uc.user_id = u.id
         GROUP BY u.id
         ORDER BY owned_count DESC, u.xp DESC
         LIMIT ?',
        [$limit]
    );
}

function getRecentCards(int $limit = 6): array {
    try {
        return dbQuery('SELECT * FROM cards WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?', [$limit]);
    } catch (Exception) {
        return [];
    }
}

function getAllCards(array $filters = [], int $page = 1, int $perPage = 24): array {
    $where  = ['c.is_active = 1'];
    $params = [];

    if (!empty($filters['rarity'])) {
        $where[]  = 'c.rarity = ?';
        $params[] = $filters['rarity'];
    }
    if (!empty($filters['search'])) {
        $where[]  = '(c.name LIKE ? OR c.character_name LIKE ?)';
        $s        = '%' . $filters['search'] . '%';
        $params[] = $s;
        $params[] = $s;
    }

    $whereStr = implode(' AND ', $where);
    $offset   = ($page - 1) * $perPage;

    try {
        $total = (int)(dbQueryOne("SELECT COUNT(*) as c FROM cards c WHERE $whereStr", $params)['c'] ?? 0);
        $cards = dbQuery(
            "SELECT c.* FROM cards c WHERE $whereStr
             ORDER BY FIELD(c.rarity,'legendaire','epique','rare','peu_commune','commune'), c.name
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    } catch (Exception) {
        return ['cards' => [], 'total' => 0, 'pages' => 0, 'current' => $page];
    }

    return ['cards' => $cards, 'total' => $total, 'pages' => (int)ceil($total / $perPage), 'current' => $page];
}

function getUserCollection(string $userId, array $filters = [], int $page = 1, int $perPage = 24): array {
    $where  = ['c.is_active = 1'];
    $params = [$userId];

    if (!empty($filters['rarity'])) {
        $where[]  = 'c.rarity = ?';
        $params[] = $filters['rarity'];
    }
    if (isset($filters['owned'])) {
        if ($filters['owned'] === 'owned')   $where[] = 'uc.id IS NOT NULL';
        if ($filters['owned'] === 'missing') $where[] = 'uc.id IS NULL';
    }

    $whereStr = implode(' AND ', $where);
    $offset   = ($page - 1) * $perPage;

    try {
        $total = (int)(dbQueryOne(
            "SELECT COUNT(*) as c FROM cards c LEFT JOIN user_cards uc ON uc.card_id = c.id AND uc.user_id = ? WHERE $whereStr",
            $params
        )['c'] ?? 0);

        $cards = dbQuery(
            "SELECT c.*, uc.obtained_at, (uc.id IS NOT NULL) as owned
             FROM cards c
             LEFT JOIN user_cards uc ON uc.card_id = c.id AND uc.user_id = ?
             WHERE $whereStr
             ORDER BY FIELD(c.rarity,'legendaire','epique','rare','peu_commune','commune'), c.name
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    } catch (Exception) {
        return ['cards' => [], 'total' => 0, 'pages' => 0, 'current' => $page];
    }

    return ['cards' => $cards, 'total' => $total, 'pages' => (int)ceil($total / $perPage), 'current' => $page];
}

function selectWeightedCard(array $cards): ?array {
    if (empty($cards)) return null;
    $total = array_sum(array_map(fn($c) => (int)$c['rarity_weight'], $cards));
    $rand = random_int(1, max(1, $total));
    foreach ($cards as $card) {
        $rand -= (int)$card['rarity_weight'];
        if ($rand <= 0) return $card;
    }
    return $cards[array_key_last($cards)];
}

function rollCardForUser(string $userId): array {
    $today = date('Y-m-d');
    $user = dbQueryOne('SELECT * FROM users WHERE id = ?', [$userId]);
    if (!$user) {
        return ['success' => false, 'reason' => 'user_not_found'];
    }

    if (($user['last_roll_reset'] ?? null) !== $today) {
        dbExecute('UPDATE users SET rolls_remaining = ?, last_roll_reset = ? WHERE id = ?', [MAX_DAILY_ROLLS, $today, $userId]);
        $user['rolls_remaining'] = MAX_DAILY_ROLLS;
    }

    if ((int)$user['rolls_remaining'] <= 0) {
        $next = new DateTime('tomorrow');
        $now = new DateTime();
        $diff = $now->diff($next);
        return ['success' => false, 'reason' => 'no_rolls', 'time_left' => $diff->format('%hh %Im')];
    }

    $cards = dbQuery('SELECT * FROM cards WHERE is_active = 1');
    $card = selectWeightedCard($cards);
    if (!$card) {
        return ['success' => false, 'reason' => 'no_cards'];
    }

    $existing = dbQueryOne('SELECT id FROM user_cards WHERE user_id = ? AND card_id = ?', [$userId, $card['id']]);
    $isDuplicate = (bool)$existing;
    if (!$isDuplicate) {
        dbExecute('INSERT INTO user_cards (user_id, card_id) VALUES (?, ?)', [$userId, $card['id']]);
    }

    $xpGained = randomXpReward($card['rarity'], $isDuplicate);
    $newXp = (int)($user['xp'] ?? 0) + $xpGained;
    $newLevel = xpToLevel($newXp);

    dbExecute(
        'INSERT INTO roll_history (user_id, card_id, was_duplicate, xp_gained) VALUES (?, ?, ?, ?)',
        [$userId, $card['id'], $isDuplicate ? 1 : 0, $xpGained]
    );
    dbExecute(
        'UPDATE users SET rolls_remaining = rolls_remaining - 1, total_rolls = total_rolls + 1, xp = ?, level = ? WHERE id = ?',
        [$newXp, $newLevel, $userId]
    );

    $updated = dbQueryOne('SELECT rolls_remaining FROM users WHERE id = ?', [$userId]);
    $displayName = $_SESSION['global_name'] ?? $_SESSION['username'] ?? 'Un joueur';
    $ownedAfter = (int)(dbQueryOne('SELECT COUNT(*) as c FROM user_cards WHERE user_id = ?', [$userId])['c'] ?? 0);
    $totalCards = (int)(dbQueryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1')['c'] ?? 0);

    if ($card['rarity'] === 'legendaire') {
        enqueueActivityEvent(
            'legendary_roll',
            $userId,
            'Roll légendaire',
            $displayName . ' vient de roll une carte légendaire : ' . $card['name'] . '.',
            APP_URL . '/card.php?id=' . $card['id'],
            ['card_id' => $card['id'], 'rarity' => $card['rarity']]
        );
    }
    if (!$isDuplicate && $ownedAfter === 1) {
        enqueueActivityEvent(
            'first_card',
            $userId,
            'Première carte obtenue',
            $displayName . ' vient de trouver sa première carte : ' . $card['name'] . '.',
            APP_URL . '/user.php?id=' . $userId,
            ['card_id' => $card['id']]
        );
    }
    if (!$isDuplicate && $totalCards > 0 && $ownedAfter >= $totalCards) {
        enqueueActivityEvent(
            'collection_complete',
            $userId,
            'Collection complète',
            $displayName . ' possède maintenant toutes les cartes disponibles.',
            APP_URL . '/user.php?id=' . $userId,
            ['owned' => $ownedAfter, 'total' => $totalCards]
        );
    }

    return [
        'success' => true,
        'card' => $card,
        'duplicate' => $isDuplicate,
        'xp_gained' => $xpGained,
        'level' => $newLevel,
        'rolls_remaining' => (int)($updated['rolls_remaining'] ?? 0),
    ];
}

// ===== UTILS =====

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF invalide.');
    }
}

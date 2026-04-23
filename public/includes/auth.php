<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bot_api.php';

function currentSessionUserIsBanned(): bool {
    if (empty($_SESSION['user_id'])) return false;
    try {
        $row = dbQueryOne('SELECT is_banned FROM users WHERE id = ?', [$_SESSION['user_id']]);
        return !empty($row['is_banned']);
    } catch (Exception) {
        return false;
    }
}

function denyBannedAccess(): void {
    session_destroy();
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Accès suspendu</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#1e1f22;color:#f2f3f5;font-family:Arial,sans-serif}.box{max-width:520px;padding:28px;border:1px solid rgba(255,255,255,.12);border-radius:8px;background:#2b2d31}h1{margin:0 0 10px;font-size:24px}p{margin:0;color:#b5bac1;line-height:1.6}</style></head><body><main class="box"><h1>Accès suspendu</h1><p>Votre compte est banni. L’accès au site et aux commandes du bot Edward est désactivé.</p></main></body></html>';
    exit;
}

if (currentSessionUserIsBanned()) {
    denyBannedAccess();
}

function isLoggedIn(): bool {
    if (empty($_SESSION['user_id'])) return false;
    if (currentSessionUserIsBanned()) denyBannedAccess();
    return true;
}

function isAdmin(): bool {
    return isLoggedIn() && (isOwner() || isCardAuthor());
}

function isOwner(): bool {
    if (!isLoggedIn()) return false;
    if ((string)$_SESSION['user_id'] === ADMIN_DISCORD_ID) return true;
    try {
        $row = dbQueryOne('SELECT is_owner FROM users WHERE id = ?', [$_SESSION['user_id']]);
        return !empty($row['is_owner']);
    } catch (Exception) {
        return false;
    }
}

function isCardAuthor(): bool {
    if (!isLoggedIn()) return false;
    try {
        $row = dbQueryOne('SELECT is_card_author FROM users WHERE id = ?', [$_SESSION['user_id']]);
        return !empty($row['is_card_author']);
    } catch (Exception) {
        return false;
    }
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/index.php?error=forbidden');
        exit;
    }
}

function requireOwner(): void {
    requireLogin();
    if (!isOwner()) {
        header('Location: ' . APP_URL . '/admin/index.php?error=owner_required');
        exit;
    }
}

function getDiscordAuthUrl(): string {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $params = http_build_query([
        'client_id'     => DISCORD_CLIENT_ID,
        'redirect_uri'  => DISCORD_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'identify',
        'state'         => $state,
        'prompt'        => 'none',
    ]);

    return 'https://discord.com/api/oauth2/authorize?' . $params;
}

function exchangeCodeForToken(string $code): ?array {
    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => DISCORD_REDIRECT_URI,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data : null;
}

function getDiscordUser(string $accessToken): ?array {
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return isset($data['id']) ? $data : null;
}

function loginUser(array $discordUser): void {
    $id          = $discordUser['id'];
    $username    = $discordUser['username'];
    $globalName  = $discordUser['global_name'] ?? null;
    $avatar      = $discordUser['avatar'] ?? null;
    $banner      = $discordUser['banner'] ?? null;

    $existing = dbQueryOne('SELECT id, is_banned FROM users WHERE id = ?', [$id]);
    if (!empty($existing['is_banned'])) {
        throw new RuntimeException('Compte banni.');
    }
    if ($existing) {
        dbExecute(
            'UPDATE users SET username = ?, global_name = ?, avatar = ?, banner = ?, is_owner = IF(id = ?, 1, is_owner), updated_at = NOW() WHERE id = ?',
            [$username, $globalName, $avatar, $banner, ADMIN_DISCORD_ID, $id]
        );
    } else {
        dbExecute(
            'INSERT INTO users (id, username, global_name, avatar, banner, is_owner) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $username, $globalName, $avatar, $banner, (string)$id === ADMIN_DISCORD_ID ? 1 : 0]
        );
    }

    $_SESSION['user_id']     = $id;
    $_SESSION['username']    = $username;
    $_SESSION['global_name'] = $globalName;
    $_SESSION['avatar']      = $avatar;
    $_SESSION['banner']      = $banner;
}

function getAvatarUrl(string $userId, ?string $avatarHash, int $size = 128): string {
    if ($avatarHash) {
        $ext = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';
        return "https://cdn.discordapp.com/avatars/{$userId}/{$avatarHash}.{$ext}?size={$size}";
    }
    $index = (int)((int)$userId >> 22) % 6;
    return "https://cdn.discordapp.com/embed/avatars/{$index}.png";
}

function syncDiscordUserProfile(string $userId): ?array {
    $discordUser = fetchDiscordUserById($userId);
    if (!$discordUser) return null;

    dbExecute(
        'UPDATE users SET username = ?, global_name = ?, avatar = ?, banner = ?, updated_at = NOW() WHERE id = ?',
        [
            $discordUser['username'] ?? 'unknown',
            $discordUser['global_name'] ?? null,
            $discordUser['avatar'] ?? null,
            $discordUser['banner'] ?? null,
            $userId,
        ]
    );

    return $discordUser;
}

function getBannerUrl(string $userId, ?string $bannerHash, int $size = 1024): string {
    if ($bannerHash) {
        $ext = str_starts_with($bannerHash, 'a_') ? 'gif' : 'png';
        return "https://cdn.discordapp.com/banners/{$userId}/{$bannerHash}.{$ext}?size={$size}";
    }
    return APP_URL . '/assets/imgs/banner.gif';
}

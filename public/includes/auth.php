<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bot_api.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
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

    $existing = dbQueryOne('SELECT id FROM users WHERE id = ?', [$id]);
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

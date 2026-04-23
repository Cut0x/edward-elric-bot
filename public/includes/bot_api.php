<?php

function fetchBotInfo(): array {
    $token = EDWARD_BOT_TOKEN;
    $cacheKey = $token ? substr(sha1($token), 0, 12) : 'fallback';
    $cacheFile = sys_get_temp_dir() . '/edwardbot_info_' . $cacheKey . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['id'])) return $cached;
    }

    if (!$token) {
        return ['username' => 'Edward Elric Bot', 'id' => '', 'avatar' => null, 'global_name' => null];
    }

    $ch = curl_init('https://discord.com/api/v10/users/@me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bot ' . $token,
            'Content-Type: application/json',
            'User-Agent: EdwardElricBot (localhost, 1.0)',
        ],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($response, true);
        if (!empty($data['id'])) {
            file_put_contents($cacheFile, json_encode($data));
            return $data;
        }
    }

    return ['username' => 'Edward Elric Bot', 'id' => '', 'avatar' => null, 'global_name' => null];
}

function getBotAvatarUrl(int $size = 128): string {
    static $url = null;
    if ($url !== null) return $url;

    $bot = fetchBotInfo();
    if (!empty($bot['avatar']) && !empty($bot['id'])) {
        $ext = str_starts_with($bot['avatar'], 'a_') ? 'gif' : 'png';
        $url = "https://cdn.discordapp.com/avatars/{$bot['id']}/{$bot['avatar']}.{$ext}?size={$size}";
    } else {
        $url = APP_URL . '/assets/imgs/banner.gif';
    }
    return $url;
}

function getBotName(): string {
    static $name = null;
    if ($name !== null) return $name;
    $bot  = fetchBotInfo();
    $name = $bot['global_name'] ?? $bot['username'] ?? 'Edward Elric Bot';
    return $name;
}

function fetchDiscordUserById(string $userId): ?array {
    $cacheFile = sys_get_temp_dir() . '/edwardbot_user_' . preg_replace('/\D+/', '', $userId) . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['id'])) return $cached;
    }

    if (!EDWARD_BOT_TOKEN) return null;

    $ch = curl_init('https://discord.com/api/v10/users/' . rawurlencode($userId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bot ' . EDWARD_BOT_TOKEN,
            'Content-Type: application/json',
            'User-Agent: EdwardElricBot (localhost, 1.0)',
        ],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;

    $data = json_decode($response, true);
    if (empty($data['id'])) return null;

    file_put_contents($cacheFile, json_encode($data));
    return $data;
}

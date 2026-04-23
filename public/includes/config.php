<?php

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(dirname(__DIR__, 2) . '/.env');

function env(string $key, mixed $default = ''): mixed {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

// Discord OAuth2
define('DISCORD_CLIENT_ID',     env('DISCORD_CLIENT_ID'));
define('DISCORD_CLIENT_SECRET', env('DISCORD_CLIENT_SECRET'));
define('DISCORD_REDIRECT_URI',  env('DISCORD_REDIRECT_URI', 'http://localhost/edward-elric-bot/public/callback.php'));
define('DISCORD_BOT_TOKEN',     env('DISCORD_BOT_TOKEN'));

// Application
define('APP_URL',    env('APP_URL',    'http://localhost/edward-elric-bot/public'));
define('APP_SECRET', env('APP_SECRET', 'default_insecure_secret'));
define('CARDS_URL',  env('CARDS_URL',  'http://localhost/edward-elric-bot/cards'));
define('CARDS_DIR',  dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cards' . DIRECTORY_SEPARATOR);
define('ICONS_URL',  rtrim(str_replace('/public', '', APP_URL), '/') . '/icons');
define('BADGES_URL', rtrim(str_replace('/public', '', APP_URL), '/') . '/badges');
define('PROPOSALS_DIR', __DIR__ . '/../uploads/proposals/');
define('PROPOSALS_URL', rtrim(APP_URL, '/') . '/uploads/proposals');

// Admin
define('ADMIN_DISCORD_ID', env('ADMIN_DISCORD_ID', '574544938440851466'));

// Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'edwardbot'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Jeu
define('MAX_DAILY_ROLLS', 10);

define('RARITIES', [
    'commune'     => ['label' => 'Commune',    'color' => '#9e9e9e', 'weight' => 6000, 'xp_bonus' => 25],
    'peu_commune' => ['label' => 'Peu Commune','color' => '#57ab71', 'weight' => 2500, 'xp_bonus' => 50],
    'rare'        => ['label' => 'Rare',       'color' => '#5b9bd5', 'weight' => 1000, 'xp_bonus' => 100],
    'epique'      => ['label' => 'Épique',     'color' => '#a070c0', 'weight' => 400,  'xp_bonus' => 200],
    'legendaire'  => ['label' => 'Légendaire', 'color' => '#e3a321', 'weight' => 100,  'xp_bonus' => 500],
]);

// Système XP
define('XP_PER_ROLL', 15);
define('XP_REWARDS', [
    'commune'     => 25,
    'peu_commune' => 50,
    'rare'        => 100,
    'epique'      => 200,
    'legendaire'  => 500,
]);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}

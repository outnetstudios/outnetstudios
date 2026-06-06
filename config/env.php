<?php

// Helper: clean a value — if it looks like an un-interpolated Wasmer var, return null
if (!function_exists('envVal')) {
function envVal(string $key): ?string
{
    $val = getenv($key);
    if ($val === false || $val === '' || $val === null) return null;
    $val = trim($val);
    // Wasmer ShipIt sometimes leaves ${VAR} un-interpolated
    if (preg_match('/^\$\{?\w+\}?$/', $val)) return null;
    // Placeholder from env.example.php
    if (str_contains($val, '_HERE') || str_contains($val, 'YOUR_')) return null;
    return $val;
}
}

// 1. Read .env file from project root (Wasmer's "Paste a .env" feature)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            // Only set if not already set (getenv doesn't override, but putenv does)
            if (getenv($k) === false || getenv($k) === '' || preg_match('/^\$\{?\w+\}?$/', trim(getenv($k)))) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}

// 2. Check environment variables with cleaning
$dbHost = envVal('DB_HOST');
if ($dbHost) {
    return [
        'db' => [
            'host' => $dbHost,
            'port' => envVal('DB_PORT') ?: '3306',
            'dbname' => envVal('DB_NAME') ?: '',
            'user' => envVal('DB_USERNAME') ?: '',
            'password' => envVal('DB_PASSWORD') ?: '',
        ],
        'mail' => [
            'mailjet_api_key' => envVal('MJ_APIKEY_PUBLIC') ?: '',
            'mailjet_secret_key' => envVal('MJ_APIKEY_PRIVATE') ?: '',
            'from_email' => envVal('MAIL_FROM_EMAIL') ?: 'no-reply@outnetstudios.great-site.net',
            'from_name' => envVal('MAIL_FROM_NAME') ?: 'Outnet Studios',
            'admin_email' => envVal('MAIL_ADMIN_EMAIL') ?: '',
        ],
    ];
}

// 3. Local PHP config file (development / legacy)
$local = __DIR__ . '/env.local.php';
if (file_exists($local)) {
    return require $local;
}

// 4. Example / template config
$example = __DIR__ . '/env.example.php';
if (file_exists($example)) {
    return require $example;
}

throw new \RuntimeException('No configuration found. Set environment variables (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD) or create config/env.local.php from config/env.example.php');

<?php

// 1. Environment variables (primary — used on Wasmer, Render, etc.)
$dbHost = getenv('DB_HOST');
if ($dbHost) {
    return [
        'db' => [
            'host' => $dbHost,
            'port' => getenv('DB_PORT') ?: '3306',
            'dbname' => getenv('DB_NAME') ?: '',
            'user' => getenv('DB_USERNAME') ?: '',
            'password' => getenv('DB_PASSWORD') ?: '',
        ],
        'mail' => [
            'mailjet_api_key' => getenv('MJ_APIKEY_PUBLIC') ?: '',
            'mailjet_secret_key' => getenv('MJ_APIKEY_PRIVATE') ?: '',
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@outnetstudios.great-site.net',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'Outnet Studios',
            'admin_email' => getenv('MAIL_ADMIN_EMAIL') ?: '',
        ],
    ];
}

// 2. Local PHP config file (development / legacy)
$local = __DIR__ . '/env.local.php';
if (file_exists($local)) {
    return require $local;
}

// 3. Example / template config
$example = __DIR__ . '/env.example.php';
if (file_exists($example)) {
    return require $example;
}

throw new \RuntimeException('No configuration found. Set environment variables (DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD) or create config/env.local.php from config/env.example.php');

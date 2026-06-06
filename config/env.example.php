<?php
/**
 * Configuration template
 *
 * En las plataformas cloud (Wasmer, Render, etc.) se usan variables de entorno.
 * En desarrollo local se copia este archivo a env.local.php y se llenan los valores.
 *
 * Variables de entorno soportadas:
 *
 *   DB_HOST       — Host de MySQL (Wasmer provee ${DB_HOST})
 *   DB_PORT       — Puerto (default 3306)
 *   DB_NAME       — Nombre BD (Wasmer provee ${DB_NAME})
 *   DB_USERNAME   — Usuario BD (Wasmer provee ${DB_USERNAME})
 *   DB_PASSWORD   — Password BD (Wasmer provee ${DB_PASSWORD})
 *
 *   MJ_APIKEY_PUBLIC   — Mailjet API Key
 *   MJ_APIKEY_PRIVATE  — Mailjet Secret Key
 *   MAIL_FROM_EMAIL    — Correo remitente
 *   MAIL_FROM_NAME     — Nombre remitente
 *   MAIL_ADMIN_EMAIL   — Correo admin para notificaciones
 */
return [
    'db' => [
        'host' => 'DB_HOST_HERE',
        'port' => '3306',
        'dbname' => 'DB_NAME_HERE',
        'user' => 'DB_USER_HERE',
        'password' => 'DB_PASSWORD_HERE',
    ],
    'mail' => [
        'mailjet_api_key' => 'MAILJET_API_KEY_HERE',
        'mailjet_secret_key' => 'MAILJET_SECRET_KEY_HERE',
        'from_email' => 'no-reply@example.com',
        'from_name' => 'Outnet Studios',
        'admin_email' => 'admin@example.com',
    ],
];

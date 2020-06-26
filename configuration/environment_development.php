<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = intval($dotenv->pop('DB_PORT', '5432'));
$db_name = 'flusio_development';

return [
    'app_name' => 'flusio',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'session_lifetime' => 30,

    'url_options' => [
        'host' => $dotenv->pop('APP_HOST'),
        'path' => $dotenv->pop('APP_PATH', '/'),
        'port' => intval($dotenv->pop('APP_PORT')),
    ],

    'application' => [
        'cache_path' => $dotenv->pop('APP_CACHE_PATH', $app_path . '/cache'),
        'demo' => filter_var($dotenv->pop('APP_DEMO'), FILTER_VALIDATE_BOOLEAN),
    ],

    'database' => [
        'dsn' => "pgsql:host={$db_host};port={$db_port};dbname={$db_name}",
        'username' => $dotenv->pop('DB_USERNAME'),
        'password' => $dotenv->pop('DB_PASSWORD'),
    ],

    'mailer' => [
        'type' => $dotenv->pop('APP_MAILER'),
        'from' => $dotenv->pop('SMTP_FROM'),
        'smtp' => [
            'domain' => $dotenv->pop('SMTP_DOMAIN'),
            'host' => $dotenv->pop('SMTP_HOST'),
            'port' => intval($dotenv->pop('SMTP_PORT')),
            'auth' => filter_var($dotenv->pop('SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN),
            'auth_type' => $dotenv->pop('SMTP_AUTH_TYPE', ''),
            'username' => $dotenv->pop('SMTP_USERNAME'),
            'password' => $dotenv->pop('SMTP_PASSWORD'),
            'secure' => $dotenv->pop('SMTP_SECURE', ''),
        ],
    ],
];

<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = $dotenv->pop('DB_PORT');
$db_name = 'flusio_development';

return [
    'app_name' => 'flusio',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'url_options' => [
        'host' => $dotenv->pop('APP_HOST'),
        'port' => intval($dotenv->pop('APP_PORT')),
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
            'auth' => (bool)$dotenv->pop('SMTP_AUTH'),
            'auth_type' => $dotenv->pop('SMTP_AUTH_TYPE'),
            'username' => $dotenv->pop('SMTP_USERNAME'),
            'password' => $dotenv->pop('SMTP_PASSWORD'),
            'secure' => $dotenv->pop('SMTP_SECURE'),
        ],
    ],
];

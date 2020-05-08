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
        'type' => getenv('APP_MAILER'),
        'from' => getenv('SMTP_FROM'),
        'smtp' => [
            'domain' => getenv('SMTP_DOMAIN'),
            'host' => getenv('SMTP_HOST'),
            'port' => intval(getenv('SMTP_PORT')),
            'auth' => (bool)getenv('SMTP_AUTH'),
            'auth_type' => getenv('SMTP_AUTH_TYPE'),
            'username' => getenv('SMTP_USERNAME'),
            'password' => getenv('SMTP_PASSWORD'),
            'secure' => getenv('SMTP_SECURE'),
        ],
    ],
];

<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = $dotenv->pop('DB_PORT');
$db_name = 'flusio_test';

return [
    'app_name' => 'flusio',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'url_options' => [
        'host' => 'test.flus.io',
        'protocol' => 'https',
    ],

    'database' => [
        'dsn' => "pgsql:host={$db_host};port={$db_port};dbname={$db_name}",
        'username' => $dotenv->pop('DB_USERNAME'),
        'password' => $dotenv->pop('DB_PASSWORD'),
    ],

    'mailer' => [
        'type' => 'mail',
        'from' => 'root@localhost',
    ],

    'no_syslog' => !getenv('APP_SYSLOG_ENABLED'),
];

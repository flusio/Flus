<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = intval($dotenv->pop('DB_PORT', '5432'));
$db_name = 'flusio_test';

$temporary_directory = sys_get_temp_dir() . '/flusio/' . bin2hex(random_bytes(6));
$data_directory = $temporary_directory . '/data';
$cache_directory = $temporary_directory . '/cache';
@mkdir($temporary_directory, 0777, true);
@mkdir($data_directory, 0777, true);
@mkdir($cache_directory, 0777, true);

return [
    'app_name' => 'flusio',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'session_lifetime' => 30,

    'url_options' => [
        'host' => 'test.flus.io',
        'protocol' => 'https',
    ],

    'application' => [
        'version' => trim(@file_get_contents($app_path . '/VERSION.txt')),
        'cache_path' => $cache_directory,
        'demo' => false,
        'registrations_opened' => true,
    ],

    'database' => [
        'dsn' => "pgsql:host={$db_host};port={$db_port};dbname={$db_name}",
        'username' => $dotenv->pop('DB_USERNAME'),
        'password' => $dotenv->pop('DB_PASSWORD'),
    ],

    'mailer' => [
        'type' => 'test',
        'from' => 'root@localhost',
    ],

    'data_path' => $data_directory,
    'no_syslog' => !getenv('APP_SYSLOG_ENABLED'),
];

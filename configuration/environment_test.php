<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = intval($dotenv->pop('DB_PORT', '5432'));
$db_name = 'flus_test';

$temporary_directory = sys_get_temp_dir() . '/flus/' . \Minz\Random::hex(10);
$data_directory = $temporary_directory . '/data';
$cache_directory = $temporary_directory . '/cache';
$media_directory = $temporary_directory . '/media';
@mkdir($temporary_directory, 0777, true);
@mkdir($data_directory, 0777, true);
@mkdir($cache_directory, 0777, true);
@mkdir($media_directory, 0777, true);

$flus_version = trim(@file_get_contents($app_path . '/VERSION.txt')) . '-test';
$user_agent = "flus/{$flus_version}";

return [
    'app_name' => 'App',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'session_lifetime' => 30,

    'url_options' => [
        'host' => 'test.flus.io',
        'protocol' => 'https',
    ],

    'application' => [
        'support_email' => $dotenv->pop('APP_SUPPORT_EMAIL'),
        'brand' => 'Flus',
        'version' => $flus_version,
        'user_agent' => $user_agent,
        'cache_path' => $cache_directory,
        'media_path' => $media_directory,
        'demo' => false,
        'registrations_opened' => true,
        'feed_what_is_new' => $dotenv->pop(
            'APP_FEED_WHAT_IS_NEW',
            'https://github.com/flusio/Flus/releases.atom'
        ),
        'subscriptions_enabled' => false, // should be enable on a case-by-case basis
        'subscriptions_host' => 'https://flus.example.com',
        'subscriptions_private_key' => 'some key',
        'feeds_links_keep_period' => 0,
        'feeds_links_keep_minimum' => 0,
        'feeds_links_keep_maximum' => 0,
        'job_feeds_sync_count' => 1,
        'job_links_sync_count' => 1,
        'server_ips' => [],
        'pocket_consumer_key' => 'some token',
        'cli_locale' => 'en_GB',
        'plausible_url' => null,
        'mock_host' => $dotenv->pop('MOCK_HOST'),
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

    'jobs_adapter' => 'test',

    'data_path' => $data_directory,
    'tmp_path' => $temporary_directory,
    'no_syslog_output' => true,
];

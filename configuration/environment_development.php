<?php

$db_host = $dotenv->pop('DB_HOST');
$db_port = intval($dotenv->pop('DB_PORT', '5432'));
$db_name = 'flus_development';

$subscriptions_host = $dotenv->pop('APP_SUBSCRIPTIONS_HOST');

$flus_version = trim(@file_get_contents($app_path . '/VERSION.txt')) . '-dev';
$user_agent = "flus/{$flus_version}";

$feeds_links_keep_minimum = max(0, intval($dotenv->pop('FEEDS_LINKS_KEEP_MINIMUM', '0')));
$feeds_links_keep_maximum = max(0, intval($dotenv->pop('FEEDS_LINKS_KEEP_MAXIMUM', '0')));
if (
    $feeds_links_keep_maximum > 0 &&
    $feeds_links_keep_maximum < $feeds_links_keep_minimum
) {
    $feeds_links_keep_minimum = $feeds_links_keep_maximum;
}
$feeds_links_keep_period = max(0, intval($dotenv->pop('FEEDS_LINKS_KEEP_PERIOD', '0')));

$job_feeds_sync_count = max(1, intval($dotenv->pop('JOB_FEEDS_SYNC_COUNT', '1')));
$job_links_sync_count = max(1, intval($dotenv->pop('JOB_LINKS_SYNC_COUNT', '1')));

$server_ips = array_map('trim', explode(',', $dotenv->pop('APP_SERVER_IPS', '')));

return [
    'app_name' => 'App',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'session_lifetime' => 30,

    'url_options' => [
        'host' => $dotenv->pop('APP_HOST'),
        'path' => $dotenv->pop('APP_PATH', '/'),
        'port' => intval($dotenv->pop('APP_PORT')),
    ],

    'application' => [
        'support_email' => $dotenv->pop('APP_SUPPORT_EMAIL', ''),
        'brand' => $dotenv->pop('APP_BRAND', 'Flus'),
        'version' => $flus_version,
        'user_agent' => $user_agent,
        'cache_path' => $dotenv->pop('APP_CACHE_PATH', $app_path . '/cache'),
        'media_path' => $dotenv->pop('APP_MEDIA_PATH', $app_path . '/public/media'),
        'demo' => filter_var($dotenv->pop('APP_DEMO', false), FILTER_VALIDATE_BOOLEAN),
        'registrations_opened' => filter_var($dotenv->pop('APP_OPEN_REGISTRATIONS', true), FILTER_VALIDATE_BOOLEAN),
        'feed_what_is_new' => $dotenv->pop(
            'APP_FEED_WHAT_IS_NEW',
            'https://github.com/flusio/Flus/releases.atom'
        ),
        'subscriptions_enabled' => $subscriptions_host !== null,
        'subscriptions_host' => $subscriptions_host,
        'subscriptions_private_key' => $dotenv->pop('APP_SUBSCRIPTIONS_PRIVATE_KEY', ''),
        'feeds_links_keep_minimum' => $feeds_links_keep_minimum,
        'feeds_links_keep_maximum' => $feeds_links_keep_maximum,
        'feeds_links_keep_period' => $feeds_links_keep_period,
        'job_feeds_sync_count' => $job_feeds_sync_count,
        'job_links_sync_count' => $job_links_sync_count,
        'server_ips' => $server_ips,
        'pocket_consumer_key' => $dotenv->pop('APP_POCKET_CONSUMER_KEY', ''),
        'cli_locale' => $dotenv->pop('CLI_LOCALE', ''),
        'plausible_url' => $dotenv->pop('APP_PLAUSIBLE_URL', ''),
        'bileto_url' => $dotenv->pop('APP_BILETO_URL', ''),
        'bileto_api_token' => $dotenv->pop('APP_BILETO_API_TOKEN', ''),
    ],

    'data_path' => $dotenv->pop('APP_DATA_PATH', $app_path . '/data'),

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
            'auth' => filter_var($dotenv->pop('SMTP_AUTH', 'false'), FILTER_VALIDATE_BOOLEAN),
            'auth_type' => $dotenv->pop('SMTP_AUTH_TYPE', ''),
            'username' => $dotenv->pop('SMTP_USERNAME'),
            'password' => $dotenv->pop('SMTP_PASSWORD'),
            'secure' => $dotenv->pop('SMTP_SECURE', ''),
        ],
    ],

    'jobs_adapter' => 'database',
];

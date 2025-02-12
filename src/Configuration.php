<?php

namespace App;

/**
 * @phpstan-type ConfigurationApplication array{
 *     'support_email': string,
 *     'brand': string,
 *     'version': string,
 *     'user_agent': string,
 *     'cache_path': string,
 *     'media_path': string,
 *     'demo': bool,
 *     'registrations_opened': bool,
 *     'feed_what_is_new': string,
 *     'subscriptions_enabled': bool,
 *     'subscriptions_host': string,
 *     'subscriptions_private_key': string,
 *     'feeds_links_keep_minimum': int,
 *     'feeds_links_keep_maximum': int,
 *     'feeds_links_keep_period': int,
 *     'job_feeds_sync_count': int,
 *     'job_links_sync_count': int,
 *     'server_ips': non-empty-string[],
 *     'pocket_consumer_key': string,
 *     'cli_locale': string,
 *     'plausible_url': string,
 *     'mock_host'?: string,
 * }
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Configuration extends \Minz\Configuration
{
    /**
     * @var ConfigurationApplication
     */
    public static array $application;
}

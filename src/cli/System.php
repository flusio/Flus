<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * Manipulate the system to setup the application.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class System
{
    /**
     * Show information about the system.
     *
     * @response 200
     */
    public function show(): Response
    {
        /** @var string */
        $app_name = \Minz\Configuration::$app_name;
        /** @var string */
        $app_version = \Minz\Configuration::$application['version'];
        /** @var bool */
        $demo_enabled = \Minz\Configuration::$application['demo'];
        /** @var bool */
        $registrations_enabled = \Minz\Configuration::$application['registrations_opened'];
        /** @var bool */
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
        /** @var bool */
        $pocket_enabled = \Minz\Configuration::$application['pocket_consumer_key'] !== null;
        /** @var int */
        $job_feeds_sync_count = \Minz\Configuration::$application['job_feeds_sync_count'];
        /** @var int */
        $job_links_sync_count = \Minz\Configuration::$application['job_links_sync_count'];
        /** @var string[] */
        $server_ips = \Minz\Configuration::$application['server_ips'];
        $server_ips = implode(', ', $server_ips);
        /** @var int */
        $feeds_links_keep_minimum = \Minz\Configuration::$application['feeds_links_keep_minimum'];
        /** @var int */
        $feeds_links_keep_maximum = \Minz\Configuration::$application['feeds_links_keep_maximum'];
        /** @var int */
        $feeds_links_keep_period = \Minz\Configuration::$application['feeds_links_keep_period'];

        $count_users = models\User::count();
        $count_users_validated = models\User::countValidated();
        $percent_users_validated = intval($count_users_validated * 100 / max(1, $count_users));
        $count_users_week = models\User::countSince(\Minz\Time::ago(1, 'week'));
        $count_users_month = models\User::countSince(\Minz\Time::ago(1, 'month'));
        $count_users_active_month = models\Session::countUsersActiveSince(\Minz\Time::ago(1, 'month'));

        $count_links = models\Link::countEstimated();
        $count_links_to_fetch = models\Link::countToFetch();

        $count_collections = models\Collection::countCollections();
        $count_collections_public = models\Collection::countCollectionsPublic();

        $count_feeds = models\Collection::countFeeds();
        $count_feeds_by_hours = models\Collection::countFeedsByHours();

        $count_requests = models\FetchLog::countEstimated();
        $count_requests_feeds = models\FetchLog::countByType('feed');
        $count_requests_links = models\FetchLog::countByType('link');
        $count_requests_images = models\FetchLog::countByType('image');
        $count_requests_by_days = models\FetchLog::countByDays();

        $info =  "{$app_name} v{$app_version}\n";
        $info .= "\n";

        $info .= "Demo " . ($demo_enabled ? 'enabled' : 'disabled') . "\n";
        $info .= "Registrations " . ($registrations_enabled ? 'enabled' : 'disabled') . "\n";
        $info .= "Subscriptions " . ($subscriptions_enabled ? 'enabled' : 'disabled') . "\n";
        $info .= "Pocket " . ($pocket_enabled ? 'enabled' : 'disabled') . "\n";
        $info .= "\n";

        $info .= "{$job_feeds_sync_count} job(s) to synchronize feeds\n";
        $info .= "{$job_links_sync_count} job(s) to synchronize links\n";
        if ($server_ips) {
            $info .= "Server IPs: {$server_ips}\n";
        }
        $info .= "\n";

        $info .= 'Feeds retention policy ';
        if ($feeds_links_keep_period > 0 || $feeds_links_keep_maximum > 0) {
            $info .= "enabled\n";
            if ($feeds_links_keep_period > 0) {
                $info .= "→ not older than {$feeds_links_keep_period} months\n";
                $info .= "→ min {$feeds_links_keep_minimum} links per feed\n";
            }
            if ($feeds_links_keep_maximum > 0) {
                $info .= "→ max {$feeds_links_keep_maximum} links per feed\n";
            }
        } else {
            $info .= "disabled\n";
        }
        $info .= "\n";

        $info .= "{$count_users} users\n";
        $info .= "→ {$count_users_month} this month\n";
        $info .= "→ {$count_users_week} this week\n";
        $info .= "→ {$percent_users_validated}% validated\n";
        $info .= "→ {$count_users_active_month} active this month\n";
        $info .= "\n";

        $info .= "{$count_links} links (estimated)\n";
        $info .= "→ {$count_links_to_fetch} to synchronize\n";
        $info .= "\n";

        $info .= "{$count_collections} collections\n";
        $info .= "→ {$count_collections_public} public\n";
        $info .= "\n";

        $info .= "{$count_feeds} feeds\n";
        foreach ($count_feeds_by_hours as $hour => $count) {
            $info .= "→ {$count} synchronized at {$hour}h\n";
        }
        $info .= "\n";

        $info .= "{$count_requests} HTTP requests (last 3 - 4 days)\n";
        $info .= "→ {$count_requests_feeds} to fetch feeds\n";
        $info .= "→ {$count_requests_links} to fetch links\n";
        $info .= "→ {$count_requests_images} to fetch images\n";
        foreach ($count_requests_by_days as $day => $count) {
            $info .= "→ {$count} on {$day}\n";
        }

        return Response::text(200, $info);
    }

    /**
     * Output a secured key.
     *
     * @response 200
     */
    public function secret(): Response
    {
        $secret = \Minz\Random::hex(128);
        return Response::text(200, $secret);
    }
}

<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;
use App\http;
use App\models;
use App\utils;

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
        $app_name = \App\Configuration::$app_name;
        $app_version = \App\Configuration::$application['version'];
        $demo_enabled = \App\Configuration::$application['demo'];
        $registrations_enabled = \App\Configuration::$application['registrations_opened'];
        $subscriptions_enabled = \App\Configuration::$application['subscriptions_enabled'];
        $pocket_enabled = \App\Configuration::$application['pocket_consumer_key'] !== '';
        $job_feeds_sync_count = \App\Configuration::$application['job_feeds_sync_count'];
        $job_links_sync_count = \App\Configuration::$application['job_links_sync_count'];
        $server_ips = \App\Configuration::$application['server_ips'];
        $server_ips = implode(', ', $server_ips);
        $feeds_links_keep_minimum = \App\Configuration::$application['feeds_links_keep_minimum'];
        $feeds_links_keep_maximum = \App\Configuration::$application['feeds_links_keep_maximum'];
        $feeds_links_keep_period = \App\Configuration::$application['feeds_links_keep_period'];

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

        return Response::text(200, $info);
    }

    /**
     * Show statistics of the system.
     *
     * @request_param string format
     *     The output format, either `plain` (default) or `csv`.
     * @request_param integer year
     *     The year to display (only for `csv` format), default is current year.
     *
     * @response 200
     */
    public function stats(Request $request): Response
    {
        $format = $request->param('format', 'plain');

        if ($format === 'csv') {
            $current_year = intval(\Minz\Time::now()->format('Y'));
            $year = $request->paramInteger('year', $current_year);

            $registrations_per_date = models\User::countPerMonth($year);
            $active_per_date = models\User::countActivePerMonth($year);

            $dates = array_keys(array_merge($registrations_per_date, $active_per_date));

            $stats_per_date = [];
            foreach ($dates as $date) {
                $count_registrations = $registrations_per_date[$date] ?? 0;
                $count_active = $active_per_date[$date] ?? 0;

                $stats_per_date[$date] = [
                    'registrations' => $count_registrations,
                    'active' => $count_active,
                ];
            }

            ksort($stats_per_date);

            return Response::ok('cli/system/stats.csv.txt', [
                'stats_per_date' => $stats_per_date,
            ]);
        } else {
            $count_users = models\User::count();
            $count_users_validated = models\User::countValidated();
            $percent_users_validated = intval($count_users_validated * 100 / max(1, $count_users));

            return Response::ok('cli/system/stats.txt', [
                'count_users' => $count_users,
                'percent_users_validated' => $percent_users_validated,
                'count_users_week' => models\User::countSince(\Minz\Time::ago(1, 'week')),
                'count_users_month' => models\User::countSince(\Minz\Time::ago(1, 'month')),
                'count_users_active_month' => models\Session::countUsersActiveSince(\Minz\Time::ago(1, 'month')),
                'count_links' => models\Link::countEstimated(),
                'count_links_to_fetch' => models\Link::countToFetch(),
                'count_collections' => models\Collection::countCollections(),
                'count_collections_public' => models\Collection::countCollectionsPublic(),
                'count_feeds' => models\Collection::countFeeds(),
                'count_feeds_by_hours' => models\Collection::countFeedsByHours(),
                'count_requests' => http\FetchLog::countEstimated(),
                'count_requests_feeds' => http\FetchLog::countByType('feed'),
                'count_requests_links' => http\FetchLog::countByType('link'),
                'count_requests_images' => http\FetchLog::countByType('image'),
                'count_requests_by_days' => http\FetchLog::countByDays(),
            ]);
        }
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

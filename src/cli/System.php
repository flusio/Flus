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
    public function show()
    {
        $app_name = \Minz\Configuration::$app_name;
        $app_version = \Minz\Configuration::$application['version'];
        $demo_enabled = \Minz\Configuration::$application['demo'];
        $registrations_enabled = \Minz\Configuration::$application['registrations_opened'];
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
        $pocket_enabled = \Minz\Configuration::$application['pocket_consumer_key'] !== null;
        $job_feeds_sync_count = \Minz\Configuration::$application['job_feeds_sync_count'];
        $job_links_sync_count = \Minz\Configuration::$application['job_links_sync_count'];
        $server_ips = implode(', ', \Minz\Configuration::$application['server_ips']);

        $count_users = models\User::count();
        $count_users_validated = models\User::daoCall('countValidated');
        $percent_users_validated = intval($count_users_validated * 100 / max(1, $count_users));
        $count_users_week = models\User::daoCall('countSince', \Minz\Time::ago(1, 'week'));
        $count_users_month = models\User::daoCall('countSince', \Minz\Time::ago(1, 'month'));

        $count_links = models\Link::daoCall('countEstimated');
        $count_links_to_fetch = models\Link::daoCall('countToFetch');

        $count_collections = models\Collection::daoCall('countCollections');
        $count_collections_public = models\Collection::daoCall('countCollectionsPublic');

        $count_feeds = models\Collection::daoCall('countFeeds');
        $count_feeds_by_hours = models\Collection::daoCall('countFeedsByHours');

        $count_requests = models\FetchLog::daoCall('countEstimated');
        $count_requests_feeds = models\FetchLog::daoCall('countByType', 'feed');
        $count_requests_links = models\FetchLog::daoCall('countByType', 'link');
        $count_requests_images = models\FetchLog::daoCall('countByType', 'image');
        $count_requests_by_days = models\FetchLog::daoCall('countByDays');

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
        $info .= "{$count_users} users\n";
        $info .= "→ {$count_users_month} this month\n";
        $info .= "→ {$count_users_week} this week\n";
        $info .= "→ {$percent_users_validated}% validated\n";
        $info .= "\n";
        $info .= "{$count_links} links\n";
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
     * @return \Minz\Response
     */
    public function secret()
    {
        $secret = utils\Random::hex(128);
        return Response::text(200, $secret);
    }

    /**
     * Call init or migrate depending on the presence of a migrations version file.
     *
     * @return \Minz\Response
     */
    public function setup()
    {
        $data_path = \Minz\Configuration::$data_path;
        $migrations_version_path = $data_path . '/migrations_version.txt';

        if (file_exists($migrations_version_path)) {
            $migrations_controller = new Migrations();
            $response = $migrations_controller->apply();
        } else {
            $response = $this->init();
        }

        yield $response;

        $code = $response->code();
        if ($code >= 200 && $code < 300) {
            yield $this->loadSeeds();
        }
    }

    /**
     * Initialize the database and set the migration version.
     *
     * @return \Minz\Response
     */
    private function init()
    {
        $app_path = \Minz\Configuration::$app_path;
        $data_path = \Minz\Configuration::$data_path;
        $schema_path = $app_path . '/src/schema.sql';
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $data_path . '/migrations_version.txt';

        \Minz\Database::reset();

        $schema = file_get_contents($schema_path);
        if ($schema) {
            $database = \Minz\Database::get();
            $result = $database->exec($schema);
            if ($result === false) {
                return Response::text(500, 'The database schema couldn’t be loaded.'); // @codeCoverageIgnore
            }
        }

        $migrator = new \Minz\Migrator($migrations_path);
        $version = $migrator->lastVersion();
        $saved = @file_put_contents($migrations_version_path, $version);
        if ($saved === false) {
            return Response::text(500, 'Cannot create the migrations version file.'); // @codeCoverageIgnore
        }

        return Response::text(200, 'The system has been initialized.');
    }

    /**
     * Execute seeds code in src/seeds.php.
     *
     * @response 201 If seeds file don’t exist
     * @response 500 If an error occurs during loading
     * @response 200 On success
     */
    private function loadSeeds()
    {
        $seeds_filepath = \Minz\Configuration::$app_path . '/src/seeds.php';
        if (!file_exists($seeds_filepath)) {
            return Response::noContent();
        }

        try {
            include_once($seeds_filepath);
            return Response::text(200, 'Seeds loaded.');
        } catch (\Exception $e) {
            $error = (string)$e;
            return Response::text(500, "Seeds can’t be loaded:\n{$error}");
        }
    }
}

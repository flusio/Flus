<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Synchronize links that weren't fetched yet.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksSync extends jobs\Job
{
    /**
     * Install the correct number of jobs in database.
     */
    public static function install()
    {
        $number_jobs_to_install = \Minz\Configuration::$application['job_links_sync_count'];
        $job_dao = new models\dao\Job();
        $links_sync_job = new LinksSync();

        $jobs = $job_dao->listBy(['name' => $links_sync_job->name]);

        $diff_count = $number_jobs_to_install - count($jobs);
        if ($diff_count > 0) {
            // If positive, we need to install more jobs
            for ($i = 0; $i < $diff_count; $i++) {
                $links_sync_job->performLater();
            }
        } elseif ($diff_count < 0) {
            // If negative, we need to uninstall some jobs
            for ($i = 0; $i < abs($diff_count); $i++) {
                $job_dao->delete($jobs[$i]['id']);
            }
        }
    }

    /**
     * Initialize the LinksSync job.
     */
    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+15 seconds';
        $this->queue = 'fetchers';
    }

    /**
     * Execute the job.
     */
    public function perform()
    {
        $fetch_service = new services\LinkFetcher();

        $links = models\Link::daoToList('listToFetch', 25);
        foreach ($links as $link) {
            $has_lock = models\Link::daoCall('lock', $link->id);
            if (!$has_lock) {
                continue;
            }

            utils\Locale::setCurrentLocale($link->owner()->locale);
            try {
                $fetch_service->fetch($link);
            } catch (\Exception $e) {
                \Minz\Log::error("Error while syncing link {$link->id}: {$e->getMessage()}");
            }

            models\Link::daoCall('unlock', $link->id);
        }
    }
}

<?php

namespace App\jobs\scheduled;

use App\models;
use App\services;
use App\utils;

/**
 * Synchronize links that weren't fetched yet.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksSync extends \Minz\Job
{
    use \App\jobs\traits\JobInSerie;

    /**
     * Install the correct number of jobs in database.
     */
    public static function install(): void
    {
        $number_jobs_to_install = \App\Configuration::$application['job_links_sync_count'];

        $jobs = self::listJobsInSerie();

        $diff_count = $number_jobs_to_install - count($jobs);
        if ($diff_count > 0) {
            // If positive, we need to install more jobs
            for ($i = 0; $i < $diff_count; $i++) {
                $links_sync_job = new self();
                $links_sync_job->performAsap();
            }
        } elseif ($diff_count < 0) {
            // If negative, we need to uninstall some jobs
            for ($i = 0; $i < abs($diff_count); $i++) {
                \Minz\Job::delete($jobs[$i]->id);
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
    public function perform(): void
    {
        $fetch_service = new services\LinkFetcher();

        $serie = $this->currentSerie();
        $links = models\Link::listToFetch(max: 25, serie: $serie);

        foreach ($links as $link) {
            $has_lock = $link->lock();
            if (!$has_lock) {
                continue;
            }

            utils\Locale::setCurrentLocale($link->owner()->locale);
            try {
                $fetch_service->fetch($link);
            } catch (\Exception $e) {
                \Minz\Log::error("Error while syncing link {$link->id}: {$e->getMessage()}");
            }

            $link->unlock();
        }
    }
}

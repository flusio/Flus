<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\services;

/**
 * Job to synchronize the feeds
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedsSync extends jobs\Job
{
    public const CYCLE_DURATION = 60;
    public const FREQUENCY = 10;

    public function __construct()
    {
        parent::__construct();
        $frequency_number = self::FREQUENCY;
        $this->frequency = "+{$frequency_number} minutes";
        $this->queue = 'fetchers';
    }

    public function perform()
    {
        $feed_fetcher_service = new services\FeedFetcher();

        // feeds are synced each 10 minutes, but we don't want to sync all at
        // once, so we distribute the synchronization on 60 minutes.
        $before = \Minz\Time::ago(self::CYCLE_DURATION, 'minutes');
        $number_to_fetch = models\Collection::daoCall('countFeedsToFetch', $before);
        $job_repetition = self::CYCLE_DURATION / self::FREQUENCY;
        $limit = max(1, intval($number_to_fetch / $job_repetition));

        $collections = models\Collection::daoToList('listFeedsToFetch', $before, $limit);
        foreach ($collections as $collection) {
            $feed_fetcher_service->fetch($collection);
        }

        $user = models\User::supportUser();
        $links_fetcher_job = new jobs\UserLinksFetcher();
        $links_fetcher_job->performLater($user->id);
    }
}

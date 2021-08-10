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
    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+15 seconds';
        $this->queue = 'fetchers';
    }

    public function perform()
    {
        $feed_fetcher_service = new services\FeedFetcher();

        $before = \Minz\Time::ago(1, 'hour');
        $collections = models\Collection::daoToList('listFeedsToFetch', $before, 25);
        foreach ($collections as $collection) {
            $feed_fetcher_service->fetch($collection);
        }
    }
}

<?php

namespace flusio\jobs;

use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Fetch feeds that weren't fetched yet.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedsFetcher extends jobs\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->queue = 'fetchers';
    }

    public function perform()
    {
        $collections = models\Collection::listBy([
            'type' => 'feed',
            'feed_fetched_at' => null,
        ]);

        $fetch_service = new services\FeedFetcher();
        foreach ($collections as $collection) {
            $fetch_service->fetch($collection);
        }
    }
}

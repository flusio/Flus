<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Fetch links that weren't fetched yet.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksFetcher extends jobs\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+15 seconds';
        $this->queue = 'fetchers';
    }

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
            $fetch_service->fetch($link);

            models\Link::daoCall('unlock', $link->id);
        }
    }
}

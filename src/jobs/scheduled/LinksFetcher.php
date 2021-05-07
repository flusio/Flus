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
        $links = models\Link::daoToList('listToFetch', 25);
        if (!$links) {
            // nope, nothing to do
            return;
        }

        // we fetch the links and save them one by one
        $fetch_service = new services\LinkFetcher();
        foreach ($links as $link) {
            utils\Locale::setCurrentLocale($link->owner()->locale);
            $fetch_service->fetch($link);
        }
    }
}

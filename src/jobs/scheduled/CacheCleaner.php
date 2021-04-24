<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\utils;

/**
 * Job to clean the cache.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CacheCleaner extends jobs\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->perform_at = \Minz\Time::relative('tomorrow 1:00');
        $this->frequency = '+1 day';
    }

    public function perform()
    {
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->clean();
    }
}

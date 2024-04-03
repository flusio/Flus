<?php

namespace App\jobs\scheduled;

use App\models;
use App\services;
use App\utils;

/**
 * Job to clean the system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Cleaner extends \Minz\Job
{
    /**
     * Install the job in database.
     */
    public static function install(): void
    {
        $cleaner_job = new self();

        if (!\Minz\Job::existsBy(['name' => $cleaner_job->name])) {
            $perform_at = \Minz\Time::relative('tomorrow 1:00');
            $cleaner_job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+1 day';
    }

    public function perform(): void
    {
        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->clean();

        $support_user = models\User::supportUser();

        models\FetchLog::deleteOlderThan(\Minz\Time::ago(3, 'days'));
        models\Token::deleteExpired();
        models\Session::deleteExpired();
        models\User::deleteNotValidatedOlderThan(\Minz\Time::ago(6, 'months'));
        models\Collection::deleteUnfollowedOlderThan($support_user->id, \Minz\Time::ago(7, 'days'));
        models\Link::deleteNotStoredOlderThan($support_user->id, \Minz\Time::ago(7, 'days'));
        /** @var int */
        $feeds_links_keep_period = \Minz\Configuration::$application['feeds_links_keep_period'];
        /** @var int */
        $feeds_links_keep_minimum = \Minz\Configuration::$application['feeds_links_keep_minimum'];
        /** @var int */
        $feeds_links_keep_maximum = \Minz\Configuration::$application['feeds_links_keep_maximum'];
        models\Link::deleteFromFeeds(
            $support_user->id,
            $feeds_links_keep_period,
            $feeds_links_keep_minimum,
            $feeds_links_keep_maximum,
        );

        if (\Minz\Configuration::$application['demo']) {
            // with these two delete, the other tables should be deleted in cascade
            models\Token::deleteAll();
            models\User::deleteAll();

            // reinitialize the support user
            models\User::supportUser();

            // Initialize a default (validated) user
            $user = services\UserCreator::create('Alix', 'demo@flus.io', 'demo');
            $user->validated_at = \Minz\Time::now();
            $user->save();
        }
    }
}

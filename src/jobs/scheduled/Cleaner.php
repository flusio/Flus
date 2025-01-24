<?php

namespace App\jobs\scheduled;

use App\http;
use App\models;
use App\services;

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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->clean();

        $support_user = models\User::supportUser();

        http\FetchLog::deleteOlderThan(\Minz\Time::ago(3, 'days'));
        models\Token::deleteExpired();
        models\Session::deleteExpired();
        models\User::deleteInactiveAndNotified(
            inactive_since: \Minz\Time::ago(12, 'months'),
            notified_since: \Minz\Time::ago(1, 'month'),
        );
        models\Collection::deleteUnfollowedOlderThan($support_user->id, \Minz\Time::ago(7, 'days'));
        models\Link::deleteNotStoredOlderThan($support_user->id, \Minz\Time::ago(7, 'days'));
        $feeds_links_keep_period = \App\Configuration::$application['feeds_links_keep_period'];
        $feeds_links_keep_minimum = \App\Configuration::$application['feeds_links_keep_minimum'];
        $feeds_links_keep_maximum = \App\Configuration::$application['feeds_links_keep_maximum'];
        models\Link::deleteFromFeeds(
            $support_user->id,
            $feeds_links_keep_period,
            $feeds_links_keep_minimum,
            $feeds_links_keep_maximum,
        );

        if (\App\Configuration::$application['demo']) {
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

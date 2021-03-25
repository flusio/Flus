<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\utils;

/**
 * Job to reset the database everyday for the demo
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ResetDemo extends jobs\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->perform_at = \Minz\Time::relative('tomorrow 2:00');
        $this->frequency = '+1 day';
    }

    public function perform()
    {
        // with these two delete, the other tables should be deleted in cascade
        models\Token::deleteAll();
        models\User::deleteAll();

        $user = models\User::init('Abby', 'demo@flus.io', 'demo');
        $user->validated_at = \Minz\Time::now();
        $user->locale = utils\Locale::currentLocale();
        $user->save();

        $bookmarks_collection = models\Collection::initBookmarks($user->id);
        $bookmarks_collection->save();
    }
}

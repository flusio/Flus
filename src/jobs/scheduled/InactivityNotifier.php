<?php

namespace App\jobs\scheduled;

use App\mailers;
use App\models;

/**
 * Job to notify the users of their inactivity.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class InactivityNotifier extends \Minz\Job
{
    /**
     * Install the job in database.
     */
    public static function install(): void
    {
        $job = new self();

        if (!\Minz\Job::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::relative('tomorrow 7:00');
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+1 day';
    }

    public function perform(): void
    {
        $inactive_since = \Minz\Time::ago(11, 'months');
        $inactive_users = models\User::listInactiveAndNotNotified($inactive_since);

        $mailer = new mailers\Users();

        foreach ($inactive_users as $user) {
            if ($user->isValidated()) {
                $success = $mailer->sendInactivityEmail($user->id);
            } else {
                $success = true;
            }

            if ($success) {
                $user->deletion_notified_at = \Minz\Time::now();
                $user->save();
            }

            $sleep_seconds = random_int(2, 5);
            sleep($sleep_seconds);
        }
    }
}

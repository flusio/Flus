<?php

namespace App\jobs;

/**
 * Job to send emails asynchronously.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Mailer extends \Minz\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->queue = 'mailers';
    }

    /**
     * Execute the given mailer action.
     */
    public function perform(string $mailer_class_name, string $mailer_action_name, mixed ...$args): void
    {
        $full_class_name = "\\App\\mailers\\{$mailer_class_name}";
        $mailer = new $full_class_name();
        $result = $mailer->$mailer_action_name(...$args);

        if (!$result) {
            throw new \Exception("The email {$mailer_class_name}#{$mailer_action_name} cannot be sent.");
        }
    }
}

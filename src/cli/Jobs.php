<?php

namespace flusio\cli;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Jobs extends \Minz\Job\Controller
{
    /**
     * (Re-)install the jobs.
     *
     * @response 200
     */
    public function install($request)
    {
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];

        \flusio\jobs\scheduled\FeedsSync::install();
        \flusio\jobs\scheduled\LinksSync::install();
        \flusio\jobs\scheduled\Cleaner::install();
        if ($subscriptions_enabled) {
            \flusio\jobs\scheduled\SubscriptionsSync::install();
        }

        return Response::text(200, 'Jobs installed.');
    }
}

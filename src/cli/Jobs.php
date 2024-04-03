<?php

namespace App\cli;

use Minz\Request;
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
    public function install(Request $request): Response
    {
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];

        \App\jobs\scheduled\FeedsSync::install();
        \App\jobs\scheduled\LinksSync::install();
        \App\jobs\scheduled\Cleaner::install();
        if ($subscriptions_enabled) {
            \App\jobs\scheduled\SubscriptionsSync::install();
        }

        return Response::text(200, 'Jobs installed.');
    }
}

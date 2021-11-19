<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    /**
     * List all the subscriptions accounts ids.
     *
     * @response 200
     */
    public function index($request)
    {
        $users = models\User::listAll();
        $output = [];
        foreach ($users as $user) {
            if (!$user->subscription_account_id) {
                continue;
            }

            $output[] = $user->subscription_account_id;
        }

        return Response::text(200, implode("\n", $output));
    }
}

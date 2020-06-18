<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;

/**
 * Manipulate the Users of the application from the CLI.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users
{
    /**
     * Clean not validated users created some months ago.
     *
     * @request_param integer since Number of months since the creation, default is 1
     *
     * @response 200
     * @response 400 If since parameter is not a number or less than 1
     *
     * @return \Minz\Response
     */
    public function clean($request)
    {
        $user_dao = new models\dao\User();

        $since = $request->param('since', 1);
        if (filter_var($since, FILTER_VALIDATE_INT) === false) {
            return Response::text(400, 'The `since` parameter must be an integer.');
        }

        $since = intval($since);
        if ($since < 1) {
            return Response::text(400, 'The `since` parameter must be greater or equal to 1.');
        }

        $db_users = $user_dao->listNotValidatedOlderThan($since, 'month');
        $users_ids = array_column($db_users, 'id');

        $number_to_delete = count($users_ids);
        if ($number_to_delete > 0) {
            $user_dao->delete($users_ids);
        }

        if ($number_to_delete === 1) {
            $text = '1 user has been deleted.';
        } else {
            $text = "{$number_to_delete} users have been deleted.";
        }
        return Response::text(200, $text);
    }
}

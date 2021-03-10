<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * Manipulate the Users of the application from the CLI.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users
{
    /**
     * Create a user.
     *
     * @request_param username
     * @request_param email
     * @request_param password
     *
     * @response 400 if one of the param is invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $username = $request->param('username');
        $email = $request->param('email');
        $password = $request->param('password');

        $user = models\User::init($username, $email, $password);
        $user->validated_at = \Minz\Time::now();
        $user->locale = utils\Locale::currentLocale();

        $errors = $user->validate();
        if ($errors) {
            $errors = implode(' ', $errors);
            return Response::text(400, "User creation failed: {$errors}");
        }

        $user->save();

        $bookmarks_collection = models\Collection::initBookmarks($user->id);
        $bookmarks_collection->save();

        return Response::text(200, "User {$user->username} ({$user->email}) has been created.");
    }

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
        $since = $request->param('since', 1);
        if (filter_var($since, FILTER_VALIDATE_INT) === false) {
            return Response::text(400, 'The `since` parameter must be an integer.');
        }

        $since = intval($since);
        if ($since < 1) {
            return Response::text(400, 'The `since` parameter must be greater or equal to 1.');
        }

        $users = models\User::daoToList('listNotValidatedOlderThan', $since, 'month');
        $users_ids = array_column($users, 'id');

        $number_to_delete = count($users_ids);
        if ($number_to_delete > 0) {
            models\User::delete($users_ids);
        }

        if ($number_to_delete === 1) {
            $text = '1 user has been deleted.';
        } else {
            $text = "{$number_to_delete} users have been deleted.";
        }
        return Response::text(200, $text);
    }
}

<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;
use flusio\services;
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
     * List all the users ordered by created_at.
     *
     * @response 200
     */
    public function index($request)
    {
        $users = models\User::listAll();
        usort($users, function ($user1, $user2) {
            if ($user1->created_at == $user2->created_at) {
                return 0;
            }

            return $user1->created_at < $user2->created_at ? -1 : 1;
        });

        $output = [];
        foreach ($users as $user) {
            $created_at = $user->created_at->format('Y-m-d');
            $validated_label = '';
            if (!$user->validated_at) {
                $validated_label = ' (not validated)';
            }
            $output[] = "{$user->id} {$created_at} {$user->email}{$validated_label}";
        }

        if (!$output) {
            $output[] = 'No users';
        }

        return Response::text(200, implode("\n", $output));
    }

    /**
     * Create a user.
     *
     * @request_param username
     * @request_param email
     * @request_param password
     *
     * @response 400 if one of the param is invalid
     * @response 200
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

        $user->initDefaultCollections();

        return Response::text(200, "User {$user->username} ({$user->email}) has been created.");
    }

    /**
     * Clean not validated users created some months ago.
     *
     * @request_param integer since Number of months since the creation, default is 1
     *
     * @response 200
     * @response 400 If since parameter is less than 1
     */
    public function clean($request)
    {
        $since = $request->paramInteger('since', 1);
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

    /**
     * Export data of the given user.
     *
     * @request_param string id
     *     The id of the user to export data.
     *
     * @response 404
     *     If the user doesn't exist
     * @response 200
     *     On sucess
     */
    public function export($request)
    {
        $user_id = $request->param('id');
        $user = models\User::find($user_id);
        if (!$user) {
            return Response::text(404, "User {$user_id} doesn’t exist.");
        }

        $data_exporter = new services\DataExporter();
        $data_filepath = $data_exporter->export($user->id);

        return Response::text(200, "User’s data have been exported successfully ({$data_filepath}).");
    }
}

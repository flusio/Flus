<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;
use App\models;
use App\services;
use App\utils;

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
     * @request_param bool to-contact
     *
     * @response 200
     */
    public function index(Request $request): Response
    {
        $to_contact = $request->paramBoolean('to-contact');

        if ($to_contact) {
            $users = models\User::listBy(['accept_contact' => true]);
        } else {
            $users = models\User::listAll();
        }

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
    public function create(Request $request): Response
    {
        $username = $request->param('username', '');
        $email = $request->param('email', '');
        $password = $request->param('password', '');

        try {
            $user = services\UserCreator::create($username, $email, $password);
        } catch (services\UserCreatorError $e) {
            $errors = implode(' ', $e->errors());
            return Response::text(400, "User creation failed: {$errors}");
        }

        // Immediately validate the user since we created it manually
        $user->validated_at = \Minz\Time::now();
        $user->save();

        return Response::text(200, "User {$user->username} ({$user->email}) has been created.");
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
    public function export(Request $request): Response
    {
        $user_id = $request->param('id', '');
        $user = models\User::find($user_id);
        if (!$user) {
            return Response::text(404, "User {$user_id} doesn’t exist.");
        }

        $exportations_path = getcwd();
        if ($exportations_path === false) {
            $exportations_path = \App\Configuration::$data_path;
        }

        $data_exporter = new services\DataExporter($exportations_path);
        $data_filepath = $data_exporter->export($user->id);

        return Response::text(200, "User’s data have been exported successfully ({$data_filepath}).");
    }

    /**
     * Validate the given user.
     *
     * @request_param string id
     *     The id of the user to validate.
     *
     * @response 404
     *     If the user doesn't exist
     * @response 400
     *     If the user has already been validated
     * @response 200
     *     On sucess
     */
    public function validate(Request $request): Response
    {
        $user_id = $request->param('id', '');
        $user = models\User::find($user_id);
        if (!$user) {
            return Response::text(404, "User {$user_id} doesn’t exist.");
        }

        if ($user->validated_at) {
            return Response::text(400, "User {$user_id} has already been validated.");
        }

        if ($user->validation_token) {
            $token = models\Token::find($user->validation_token);
            if ($token) {
                models\Token::delete($token->token);
                $user->validation_token = null;
            }
        }

        $user->validated_at = \Minz\Time::now();

        $sub_enabled = \App\Configuration::$application['subscriptions_enabled'];
        if ($sub_enabled) {
            $sub_host = \App\Configuration::$application['subscriptions_host'];
            $sub_private_key = \App\Configuration::$application['subscriptions_private_key'];
            $subscriptions_service = new services\Subscriptions(
                $sub_host,
                $sub_private_key,
            );

            $account = $subscriptions_service->account($user->email);
            if ($account) {
                $user->subscription_account_id = $account['id'];
                $user->subscription_expired_at = $account['expired_at'];
            } else {
                \Minz\Log::error("Can’t get a subscription account for user {$user->id}."); // @codeCoverageIgnore
            }
        }

        $user->save();

        return Response::text(200, "User {$user_id} is now validated.");
    }
}

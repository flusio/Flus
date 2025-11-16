<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Account extends BaseController
{
    /**
     * Show the main account page.
     *
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(): Response
    {
        $user = auth\CurrentUser::require();

        $sub_enabled = \App\Configuration::areSubscriptionsEnabled();
        if ($sub_enabled && $user->hasSubscriptionAccount() && $user->isSubscriptionOverdue()) {
            $service = new services\Subscriptions();
            $service->refreshExpiredAt($user);
            $user->save();
        }

        return Response::ok('my/account/show.phtml', [
            'subscriptions_enabled' => $sub_enabled,
        ]);
    }

    /**
     * Show the delete form.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function deletion(): Response
    {
        auth\CurrentUser::require();

        return Response::ok('my/account/deletion.phtml', [
            'form' => new forms\users\DeleteAccount(),
        ]);
    }

    /**
     * Delete the current user.
     *
     * @request_param string password
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /login
     *     On success
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function delete(Request $request): Response
    {
        $current_user = auth\CurrentUser::require();

        $form = new forms\users\DeleteAccount(options: [
            'user' => $current_user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/account/deletion.phtml', [
                'form' => $form,
            ]);
        }

        if ($current_user->avatar_filename) {
            $media_path = \App\Configuration::$application['media_path'];
            $filename = $current_user->avatar_filename;
            $subpath = utils\Belt::filenameToSubpath($filename);
            @unlink("{$media_path}/avatars/{$subpath}/{$filename}");
        }

        auth\CurrentUser::deleteSession();
        $current_user->remove();

        \Minz\Flash::set('status', 'user_deleted');
        return Response::redirect('login');
    }
}

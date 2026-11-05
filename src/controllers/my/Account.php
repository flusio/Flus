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

        $subscription = new models\Subscription($user);

        $sub_enabled = \App\Configuration::areSubscriptionsEnabled();
        if ($sub_enabled && $subscription->hasAccount() && $subscription->isOverdue()) {
            $service = new services\Subscriptions();
            $service->refreshExpiredAt($user);
            $user->save();
        }

        return Response::ok('my/account/show.html.twig', [
            'subscriptions_enabled' => $sub_enabled,
            'subscription' => $subscription,
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

        return Response::ok('my/account/deletion.html.twig', [
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
            return Response::badRequest('my/account/deletion.html.twig', [
                'form' => $form,
            ]);
        }

        auth\CurrentUser::deleteSession();
        $current_user->remove();

        // The file can be shared with other users, so it is removed only if
        // nobody uses it anymore.
        $avatar_filename = $current_user->avatar_filename;
        if (
            $avatar_filename &&
            !models\User::existsBy(['avatar_filename' => $avatar_filename])
        ) {
            $image_service = new services\Image();
            $image_service->remove('avatars', $avatar_filename);
        }

        utils\Notification::success(_('Your account and your data are now deleted.'));

        return Response::redirect('login');
    }
}

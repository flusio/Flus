<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\services;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Account extends BaseController
{
    /**
     * Show the main account page.
     *
     * @response 302 /login?redirect_to=/account
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show(): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account'));

        $sub_enabled = \App\Configuration::$application['subscriptions_enabled'];
        if ($sub_enabled && $user->subscription_account_id && $user->isSubscriptionOverdue()) {
            $sub_host = \App\Configuration::$application['subscriptions_host'];
            $sub_private_key = \App\Configuration::$application['subscriptions_private_key'];

            $service = new services\Subscriptions($sub_host, $sub_private_key);

            $expired_at = $service->expiredAt($user->subscription_account_id);

            if ($expired_at) {
                $user->subscription_expired_at = $expired_at;
                $user->save();
            } else {
                \Minz\Log::error("Can’t get the expired_at for user {$user->id}.");
            }
        }

        $pocket_enabled = \App\Configuration::$application['pocket_consumer_key'] !== '';

        return Response::ok('my/account/show.phtml', [
            'subscriptions_enabled' => $sub_enabled,
            'pocket_enabled' => $pocket_enabled,
        ]);
    }

    /**
     * Show the delete form.
     *
     * @response 302 /login?redirect_to=/my/account/deletion
     *     If the user is not connected
     * @response 200
     *     On success
     */
    public function deletion(): Response
    {
        $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account deletion'));

        return Response::ok('my/account/deletion.phtml');
    }

    /**
     * Delete the current user.
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/account/delete
     *     If the user is not connected
     * @response 400
     *     If CSRF token or password is wrong
     * @response 400
     *     If trying to delete the demo account if demo is enabled
     * @response 302 /login
     *     On success
     */
    public function delete(Request $request): Response
    {
        $current_user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account deletion'));
        $password = $request->parameters->getString('password', '');
        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('my/account/deletion.phtml', [
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if (!$current_user->verifyPassword($password)) {
            return Response::badRequest('my/account/deletion.phtml', [
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        $demo = \App\Configuration::$application['demo'];
        if ($demo && $current_user->email === 'demo@flus.io') {
            return Response::badRequest('my/account/deletion.phtml', [
                'error' => _('Sorry but you cannot delete the demo account 😉'),
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

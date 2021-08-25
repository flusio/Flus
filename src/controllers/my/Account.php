<?php

namespace flusio\controllers\my;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Account
{
    /**
     * Show the main account page.
     *
     * @response 302 /login?redirect_to=/account
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        $app_conf = \Minz\Configuration::$application;
        if ($app_conf['subscriptions_enabled'] && $user->isSubscriptionOverdue()) {
            $service = new services\Subscriptions(
                $app_conf['subscriptions_host'],
                $app_conf['subscriptions_private_key']
            );
            $expired_at = $service->expiredAt($user->subscription_account_id);
            if ($expired_at) {
                $user->subscription_expired_at = $expired_at;
                $user->save();
            } else {
                \Minz\Log::error("Can’t get the expired_at for user {$user->id}.");
            }
        }

        return Response::ok('my/account/show.phtml', [
            'subscriptions_enabled' => $app_conf['subscriptions_enabled'],
            'pocket_enabled' => isset($app_conf['pocket_consumer_key']),
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
    public function deletion()
    {
        if (!auth\CurrentUser::get()) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account deletion'),
            ]);
        }

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
    public function delete($request)
    {
        $current_user = auth\CurrentUser::get();
        $password = $request->param('password');
        $csrf = $request->param('csrf');

        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account deletion'),
            ]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
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

        $demo = \Minz\Configuration::$application['demo'];
        if ($demo && $current_user->email === 'demo@flus.io') {
            return Response::badRequest('my/account/deletion.phtml', [
                'error' => _('Sorry but you cannot delete the demo account 😉'),
            ]);
        }

        if ($current_user->avatar_filename) {
            $media_path = \Minz\Configuration::$application['media_path'];
            @unlink("{$media_path}/avatars/{$current_user->avatar_filename}");
        }

        models\User::delete($current_user->id);
        auth\CurrentUser::reset();
        utils\Flash::set('status', 'user_deleted');
        return Response::redirect('login');
    }
}

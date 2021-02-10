<?php

namespace flusio\my;

use Minz\Response;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscription
{
    /** @var boolean */
    private $enabled;

    /** @var \flusio\services\Subscriptions */
    private $service;

    public function __construct()
    {
        $app_conf = \Minz\Configuration::$application;
        $this->enabled = $app_conf['subscriptions_enabled'];
        if ($this->enabled) {
            $this->service = new services\Subscriptions(
                $app_conf['subscriptions_host'],
                $app_conf['subscriptions_private_key']
            );
        }
    }

    /**
     * Create a subscription account for the current user.
     *
     * @request_param string csrf
     *
     * @response 404
     *     If subscriptions are not enabled (need a host and a key)
     * @response 302 /login?redirect_to=/my/account
     *     If the user is not connected
     * @response 302
     * @flash error
     *     If CSRF token is invalid or user is not validated yet or if an error
     *     occurs during account creation
     * @response 200
     *     If the user aleady has an account, or on successful creation
     */
    public function create($request)
    {
        if (!$this->enabled) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        if ($user->subscription_account_id) {
            return Response::redirect('account');
        }

        if (!$user->validated_at) {
            return Response::redirect('account');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('account');
        }

        $account = $this->service->account($user->email);
        if (!$account) {
            \Minz\Log::error("Can’t get a subscription account for user {$user->id}.");
            utils\Flash::set(
                'error',
                _('An error occured when getting you a subscription account, please contact the support.')
            );
            return Response::redirect('account');
        }

        $user->subscription_account_id = $account['id'];
        $user->subscription_expired_at = $account['expired_at'];
        models\User::save($user);
        return Response::redirect('account');
    }

    /**
     * Redirect to the renew page (subscription account)
     *
     * @response 404
     *     If subscriptions are not enabled (need a host and a key)
     * @response 302 /login?redirect_to=/my/account
     *     If the user is not connected
     * @response 400
     *     If the user has no account_id
     * @response 500
     *     If an error occurs when getting the redirection URL
     * @response 302 subscriptions_host/account
     *     On success
     */
    public function redirect($request)
    {
        if (!$this->enabled) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        if (!$user->subscription_account_id) {
            return Response::badRequest('bad_request.phtml');
        }

        $url = $this->service->loginUrl($user->subscription_account_id);
        if ($url) {
            return Response::found($url);
        } else {
            \Minz\Log::error("Can’t get the subscription login URL for user {$user->id}.");
            return Response::internalServerError('internal_server_error.phtml', [
                'details' => _('An error occured while logging you, please contact the support.'),
            ]);
        }
    }
}

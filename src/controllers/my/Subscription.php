<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscription extends BaseController
{
    private bool $enabled;

    private services\Subscriptions $service;

    public function __construct()
    {
        $sub_enabled = \App\Configuration::$application['subscriptions_enabled'];
        $this->enabled = $sub_enabled;
        if ($this->enabled) {
            $sub_host = \App\Configuration::$application['subscriptions_host'];
            $sub_private_key = \App\Configuration::$application['subscriptions_private_key'];
            $this->service = new services\Subscriptions($sub_host, $sub_private_key);
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
    public function create(Request $request): Response
    {
        if (!$this->enabled) {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account'));

        if ($user->subscription_account_id) {
            return Response::redirect('account');
        }

        if (!$user->validated_at) {
            return Response::redirect('account');
        }

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('account');
        }

        $account = $this->service->account($user->email);
        if (!$account) {
            \Minz\Log::error("Can’t get a subscription account for user {$user->id}.");
            \Minz\Flash::set(
                'error',
                _('An error occured when getting you a subscription account, please contact the support.')
            );
            return Response::redirect('account');
        }

        $user->subscription_account_id = $account['id'];
        $user->subscription_expired_at = $account['expired_at'];
        $user->save();
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
    public function redirect(Request $request): Response
    {
        if (!$this->enabled) {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account'));

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

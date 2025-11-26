<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscription extends BaseController
{
    /**
     * Create a subscription account for the current user.
     *
     * @request_param string csrf_token
     *
     * @response 404
     *     If subscriptions are not enabled.
     * @response 302 /my/account
     * @flash error
     *     If the CSRF token is invalid, the account is not validated, or if an
     *     error occurs during account creation.
     * @response 302 /my/account
     *     If the user aleady has an account, or on successful creation.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        if (!\App\Configuration::areSubscriptionsEnabled()) {
            return Response::notFound('errors/not_found.html.twig');
        }

        $user = auth\CurrentUser::require();

        if ($user->hasSubscriptionAccount()) {
            return Response::redirect('account');
        }

        if (!$user->isValidated()) {
            \Minz\Flash::set('error', _('You must verify your account first.'));
            return Response::redirect('account');
        }

        $form = new forms\users\InitSubscription();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::redirect('account');
        }

        $subscription_service = new services\Subscriptions();
        $result = $subscription_service->initAccount($user);

        if (!$result) {
            \Minz\Flash::set(
                'error',
                _('An error occured when getting you a subscription account, please contact the support.')
            );
            return Response::redirect('account');
        }

        $user->save();

        return Response::redirect('account');
    }

    /**
     * Redirect to the renew page (subscription account).
     *
     * @response 404
     *     If subscriptions are not enabled.
     * @response 500
     *     If the user has no account_id, or if an error occurs when getting
     *     the redirection URL.
     * @response 302 subscriptions_host/account
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function redirect(Request $request): Response
    {
        if (!\App\Configuration::areSubscriptionsEnabled()) {
            return Response::notFound('errors/not_found.html.twig');
        }

        $user = auth\CurrentUser::require();

        if (!$user->hasSubscriptionAccount()) {
            \Minz\Log::error("User {$user->id} does not have a subscription account.");
            return Response::internalServerError('errors/internal_server_error.html.twig');
        }

        $subscription_service = new services\Subscriptions();
        $url = $subscription_service->loginUrl($user);

        if (!$url) {
            \Minz\Log::error("Can’t get the subscription login URL for user {$user->id}.");
            return Response::internalServerError('errors/internal_server_error.html.twig');
        }

        return Response::found($url);
    }
}

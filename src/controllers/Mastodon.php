<?php

namespace App\controllers;

use App\auth;
use App\models;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Mastodon extends BaseController
{
    /**
     * Show the page to configure the Mastodon host/sharing.
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 200
     *     On success.
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $mastodon_account = models\MastodonAccount::findBy(['user_id' => $user->id]);
        if ($mastodon_account && !$mastodon_account->access_token) {
            $mastodon_account = null;
        }

        if ($mastodon_account) {
            $link_to_comment = $mastodon_account->options['link_to_comment'];
            $post_scriptum = $mastodon_account->options['post_scriptum'];
        } else {
            $link_to_comment = 'auto';
            $post_scriptum = '';
        }

        return Response::ok('mastodon/show.phtml', [
            'host' => '',
            'mastodon_account' => $mastodon_account,
            'link_to_comment' => $link_to_comment,
            'post_scriptum' => $post_scriptum,
        ]);
    }

    /**
     * Request the access to a Mastodon app.
     *
     * @request_param string host
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 302 /mastodon
     * @flash error
     *    If the CSRF token is invalid or if an error occurs.
     * @response 302 /mastodon
     * @flash errors
     *    If the host is invalid.
     * @response 302 :host
     *     On success.
     */
    public function requestAccess(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $host = $request->parameters->getString('host', '');
        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('mastodon');
        }

        $host = \SpiderBits\Url::sanitize($host);

        if (!\SpiderBits\Url::isValid($host)) {
            \Minz\Flash::set('errors', [
                'host' => _('The URL is invalid.'),
            ]);
            return Response::redirect('mastodon');
        }

        try {
            $mastodon_service = services\Mastodon::get($host);
        } catch (services\MastodonError $e) {
            \Minz\Log::error($e->getMessage());

            \Minz\Flash::set('errors', [
                'host' => _('The Mastodon host returned an error, please try later.'),
            ]);
            return Response::redirect('mastodon');
        }

        $mastodon_account = models\MastodonAccount::findOrCreate(
            $mastodon_service->server,
            $user,
        );

        if ($mastodon_account->access_token) {
            return Response::redirect('mastodon');
        }

        $auth_url = $mastodon_service->authorizationUrl();
        return Response::found($auth_url);
    }

    /**
     * Handle the authorization redirection from a Mastodon host.
     *
     * @request_param string code
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 302 /mastodon
     *     If the code is not given, or if the user has no associated
     *     MastodonAccount, or if the existing MastodonAccount is already
     *     authorized.
     * @response 200
     *     On success.
     */
    public function authorization(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $code = $request->parameters->getString('code', '');

        if (!$code) {
            return Response::redirect('mastodon');
        }

        $mastodon_account = models\MastodonAccount::findBy([
            'user_id' => $user->id,
        ]);

        if (!$mastodon_account || $mastodon_account->access_token) {
            return Response::redirect('mastodon');
        }

        return Response::ok('mastodon/authorization.phtml', [
            'code' => $code,
        ]);
    }

    /**
     * Get an access token for the user Mastodon account.
     *
     * @request_param string code
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 302 /mastodon
     *     If the code is not given, or if the user has no associated
     *     MastodonAccount.
     * @response 400
     *    If the CSRF token is invalid or if an error occurs.
     * @response 302 /mastodon
     *     On success.
     */
    public function authorize(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $code = $request->parameters->getString('code', '');
        $csrf = $request->parameters->getString('csrf', '');

        if (!$code) {
            return Response::redirect('mastodon');
        }

        $mastodon_account = models\MastodonAccount::findBy([
            'user_id' => $user->id,
        ]);

        if (!$mastodon_account || $mastodon_account->access_token) {
            return Response::redirect('mastodon');
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('mastodon/authorization.phtml', [
                'code' => $code,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $mastodon_server = $mastodon_account->server();
        $mastodon_service = new services\Mastodon($mastodon_server);

        try {
            $access_token = $mastodon_service->accessToken($code);
        } catch (services\MastodonError $e) {
            \Minz\Log::error($e->getMessage());

            return Response::badRequest('mastodon/authorization.phtml', [
                'code' => $code,
                'error' => _('The Mastodon host returned an error, please try later.'),
            ]);
        }

        $mastodon_account->access_token = $access_token;

        try {
            $username = $mastodon_service->getUsername($mastodon_account);
            $mastodon_account->username = $username;
        } catch (services\MastodonError $e) {
            \Minz\Log::error($e->getMessage());

            return Response::badRequest('mastodon/authorization.phtml', [
                'code' => $code,
                'error' => _('The Mastodon host returned an error, please try later.'),
            ]);
        }

        $mastodon_account->save();

        return Response::redirect('mastodon');
    }

    /**
     * Update the options of the MastodonAccount of the current user.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 302 /mastodon
     *     If the user has no associated MastodonAccount.
     * @response 400
     *    If the CSRF token is invalid or if the post_scriptum length is more
     *    than 100 characters.
     * @response 302 /mastodon
     *     On success.
     */
    public function update(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $mastodon_account = models\MastodonAccount::findBy(['user_id' => $user->id]);

        if (!$mastodon_account || !$mastodon_account->access_token) {
            return Response::redirect('mastodon');
        }

        $link_to_comment = $request->parameters->getString('link_to_comment', 'auto');
        $post_scriptum = $request->parameters->getString('post_scriptum', '');
        $csrf = $request->parameters->getString('csrf', '');

        if (
            $link_to_comment !== 'always' &&
            $link_to_comment !== 'never' &&
            $link_to_comment !== 'auto'
        ) {
            $link_to_comment = 'auto';
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('mastodon/show.phtml', [
                'host' => '',
                'mastodon_account' => $mastodon_account,
                'link_to_comment' => $link_to_comment,
                'post_scriptum' => $post_scriptum,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if (mb_strlen($post_scriptum) > 100) {
            return Response::badRequest('mastodon/show.phtml', [
                'host' => '',
                'mastodon_account' => $mastodon_account,
                'link_to_comment' => $link_to_comment,
                'post_scriptum' => $post_scriptum,
                'errors' => [
                    'post_scriptum' => 'The label must be less than 100 characters.',
                ],
            ]);
        }

        $mastodon_account->options = [
            'link_to_comment' => $link_to_comment,
            'post_scriptum' => $post_scriptum,
        ];
        $mastodon_account->save();

        return Response::redirect('mastodon');
    }

    /**
     * Remove the MastodonAccount of the current user.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/mastodon
     *     If the user is not connected.
     * @response 302 /mastodon
     *     If the user has no associated MastodonAccount or if the CSRF token
     *     is invalid.
     * @response 302 /mastodon
     *     On success.
     */
    public function disconnect(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('mastodon'));

        $csrf = $request->parameters->getString('csrf', '');

        $mastodon_account = models\MastodonAccount::findBy(['user_id' => $user->id]);

        if (!$mastodon_account || !\App\Csrf::validate($csrf)) {
            return Response::redirect('mastodon');
        }

        $mastodon_account->remove();

        return Response::redirect('mastodon');
    }
}

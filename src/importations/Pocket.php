<?php

namespace flusio\importations;

use Minz\Response;
use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pocket
{
    /**
     * Display the Pocket importation main page
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket
     *    If the user is not connected
     * @response 200
     */
    public function show($request)
    {
        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('pocket'),
            ]);
        }

        return Response::ok('importations/pocket/show.phtml');
    }

    /**
     * Do nothing for now
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket
     *    If the user is not connected
     * @response 200
     */
    public function import($request)
    {
        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('pocket'),
            ]);
        }

        return Response::ok('importations/pocket/show.phtml');
    }

    /**
     * Get a request token for the current user and redirect to Pocket
     *
     * @request_param string $csrf
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket
     *    If the user is not connected
     * @response 302 /pocket
     * @flash error
     *    If the CSRF token is invalid
     * @response 302 https://getpocket.com/auth/authorize?request_token=:token&redirect_url=:url
     */
    public function requestAccess($request)
    {
        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('pocket'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('pocket');
        }

        $consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        $redirect_uri = \Minz\Url::absoluteFor('pocket auth');
        $request_token = $pocket_service->requestToken($redirect_uri);
        $user->pocket_request_token = $request_token;
        $user->save();

        $auth_url = $pocket_service->authorizationUrl($request_token, $redirect_uri);
        return Response::found($auth_url);
    }

    /**
     * Display the Pocket authorization waiting page
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket/auth
     *    If the user is not connected
     * @response 302 /pocket
     *    If the user has no request token
     * @response 200
     */
    public function authorization($request)
    {
        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('pocket auth'),
            ]);
        }

        if (!$user->pocket_request_token) {
            return Response::redirect('pocket');
        }

        return Response::ok('importations/pocket/authorization.phtml');
    }

    /**
     * Transform a Pocket request token to an access token
     *
     * @request_param string $csrf
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket/auth
     *    If the user is not connected
     * @response 302 /pocket
     *    If the user has not request token
     * @response 400
     *    If the CSRF token is invalid
     * @response 302 /pocket
     */
    public function authorize($request)
    {
        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('pocket auth'),
            ]);
        }

        if (!$user->pocket_request_token) {
            return Response::redirect('pocket');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('importations/pocket/authorization.phtml', [
                'error' => _('A security verification failed.'),
            ]);
        }

        $consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        list($access_token, $username) = $pocket_service->accessToken($user->pocket_request_token);

        $user->pocket_access_token = $access_token;
        $user->pocket_username = $username;
        $user->pocket_request_token = null;
        $user->save();

        return Response::redirect('pocket');
    }
}

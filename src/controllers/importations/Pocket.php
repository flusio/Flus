<?php

namespace App\controllers\importations;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\jobs;
use App\models;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pocket extends BaseController
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
    public function show(Request $request): Response
    {
        if (\App\Configuration::$application['pocket_consumer_key'] === '') {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('pocket'));

        $importation = models\Importation::findBy([
            'type' => 'pocket',
            'user_id' => $user->id,
        ]);
        return Response::ok('importations/pocket/show.phtml', [
            'importation' => $importation,
            'pocket_account' => models\PocketAccount::findBy(['user_id' => $user->id]),
        ]);
    }

    /**
     * Initialize a new Pocket importation and register a PocketImportator job.
     *
     * @request_param string $csrf
     * @request_param boolean $import_bookmarks
     * @request_param boolean $import_favorites
     *
     * @response 404
     *    If the pocket_consumer_key is not set in conf
     * @response 302 /login?redirect_to=/pocket
     *    If the user is not connected
     * @response 400
     *    If the CSRF token is invalid, if user has no access token or if an
     *    import of pocket type already exists
     * @response 302 /pocket
     *    On success
     */
    public function import(Request $request): Response
    {
        if (\App\Configuration::$application['pocket_consumer_key'] === '') {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('pocket'));

        $pocket_account = models\PocketAccount::findBy([
            'user_id' => $user->id,
        ]);

        $importation = models\Importation::findBy([
            'type' => 'pocket',
            'user_id' => $user->id,
        ]);
        if ($importation) {
            return Response::badRequest('importations/pocket/show.phtml', [
                'importation' => $importation,
                'pocket_account' => $pocket_account,
                'error' => _('You already have an ongoing Pocket importation.')
            ]);
        }

        if (!$pocket_account || !$pocket_account->access_token) {
            return Response::badRequest('importations/pocket/show.phtml', [
                'importation' => null,
                'pocket_account' => $pocket_account,
                'error' => _('You didn’t authorize us to access your Pocket data.'),
            ]);
        }

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('importations/pocket/show.phtml', [
                'importation' => null,
                'pocket_account' => $pocket_account,
                'error' => _('A security verification failed.'),
            ]);
        }

        $options = [
            'import_bookmarks' => $request->parameters->getBoolean('import_bookmarks'),
            'import_favorites' => $request->parameters->getBoolean('import_favorites'),
        ];

        $importation = new models\Importation('pocket', $user->id, $options);
        $importation->save();
        $importator_job = new jobs\PocketImportator();
        $importator_job->performAsap($importation->id);

        return Response::redirect('pocket');
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
     *    If the CSRF token is invalid or if Pocket returns an error
     * @response 302 https://getpocket.com/auth/authorize?request_token=:token&redirect_url=:url
     */
    public function requestAccess(Request $request): Response
    {
        if (\App\Configuration::$application['pocket_consumer_key'] === '') {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('pocket'));

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('pocket');
        }

        $consumer_key = \App\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        try {
            $redirect_uri = \Minz\Url::absoluteFor('pocket auth');
            $request_token = $pocket_service->requestToken($redirect_uri);

            $pocket_account = new models\PocketAccount($user->id);
            $pocket_account->request_token = $request_token;
            $pocket_account->save();

            $auth_url = $pocket_service->authorizationUrl($request_token, $redirect_uri);
            return Response::found($auth_url);
        } catch (services\PocketError $e) {
            \Minz\Flash::set('error', $e->getMessage());
            return Response::redirect('pocket');
        }
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
    public function authorization(Request $request): Response
    {
        if (\App\Configuration::$application['pocket_consumer_key'] === '') {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('pocket auth'));

        $pocket_account = models\PocketAccount::findBy([
            'user_id' => $user->id,
        ]);

        if (!$pocket_account || !$pocket_account->request_token) {
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
     *    If the user has no request token
     * @response 400
     *    If the CSRF token is invalid
     * @response 302 /pocket
     * @flash error
     *    If Pocket returns an error
     * @response 302 /pocket
     */
    public function authorize(Request $request): Response
    {
        if (\App\Configuration::$application['pocket_consumer_key'] === '') {
            return Response::notFound('not_found.phtml');
        }

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('pocket auth'));

        $pocket_account = models\PocketAccount::findBy([
            'user_id' => $user->id,
        ]);

        if (!$pocket_account || !$pocket_account->request_token) {
            return Response::redirect('pocket');
        }

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('importations/pocket/authorization.phtml', [
                'error' => _('A security verification failed.'),
            ]);
        }

        $consumer_key = \App\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        try {
            list($access_token, $username) = $pocket_service->accessToken($pocket_account->request_token);

            $pocket_account->access_token = $access_token;
            $pocket_account->username = $username;
            $pocket_account->request_token = null;
            $pocket_account->save();
        } catch (services\PocketError $e) {
            $pocket_account->request_token = null;
            $pocket_account->error = $e->getCode();
            $pocket_account->save();
            \Minz\Flash::set('error', $e->getMessage());
        }

        return Response::redirect('pocket');
    }
}

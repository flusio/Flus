<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions extends BaseController
{
    /**
     * List the sessions of the current user
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws auth\PasswordNotConfirmedError
     *     If the password is not confirmed.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        auth\CurrentUser::requireConfirmedPassword();

        $session = auth\CurrentUser::session();
        assert($session !== null);

        $sessions = models\Session::listBy([
            'user_id' => $user->id,
        ], 'created_at DESC');

        return Response::ok('my/sessions/index.phtml', [
            'current_session' => $session,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Delete the given session.
     *
     * @request_param string id
     *
     * @response 302 /my/sessions
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 /my/sessions
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws auth\PasswordNotConfirmedError
     *     If the password is not confirmed.
     * @throws \Minz\Errors\MissingRecordError
     *     If the session doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the session.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        auth\CurrentUser::requireConfirmedPassword();

        $session = models\Session::requireFromRequest($request);

        auth\Access::require($user, 'delete', $session);

        $form = new forms\security\DeleteSession();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::redirect('sessions');
        }

        $response = Response::redirect('sessions');

        $current_session = auth\CurrentUser::session();
        assert($current_session !== null);

        if ($session->id === $current_session->id) {
            auth\CurrentUser::deleteSession();
            $response->removeCookie('session_token');
            $response->removeCookie('flusio_session_token');
        } else {
            $session->remove();
        }

        return $response;
    }
}

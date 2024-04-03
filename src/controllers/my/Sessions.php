<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions
{
    /**
     * List the sessions of the current user
     *
     * @response 302 /login?redirect_to=/my/sessions
     *     If the user is not connected.
     * @response 302 /my/security/confirmation?from=/my/sessions
     *     If the password is not confirmed.
     * @response 200
     *     On success.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('sessions'),
            ]);
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        if (!$session->isPasswordConfirmed()) {
            return Response::redirect('password confirmation', [
                'from' => \Minz\Url::for('sessions'),
            ]);
        }

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
     * @response 302 /login?redirect_to=/my/sessions
     *     If the user is not connected.
     * @response 302 /my/security/confirmation?from=/my/sessions
     *     If the password is not confirmed.
     * @response 404
     *     If the session doesn't exist.
     * @response 302 /my/sessions
     *     If the CSRF token is invalid.
     * @response 302 /my/sessions
     *     On success.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('sessions'),
            ]);
        }

        $current_session = auth\CurrentUser::session();

        assert($current_session !== null);

        if (!$current_session->isPasswordConfirmed()) {
            return Response::redirect('password confirmation', [
                'from' => \Minz\Url::for('sessions'),
            ]);
        }

        $session_id = $request->param('id', '');
        $csrf = $request->param('csrf', '');

        $session = models\Session::findBy([
            'id' => $session_id,
            'user_id' => $user->id,
        ]);

        if (!$session) {
            return Response::notFound('not_found.phtml');
        }

        $response = Response::redirect('sessions');

        if (\Minz\Csrf::validate($csrf)) {
            models\Token::delete($session->token);
            $session->remove();

            if ($session->id === $current_session->id) {
                auth\CurrentUser::reset();
                $response->removeCookie('session_token');
                $response->removeCookie('flusio_session_token');
            }
        }

        return $response;
    }
}

<?php

namespace flusio\controllers\my;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;

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
}

<?php

namespace flusio\my;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Security
{
    /**
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('security'),
            ]);
        }

        $session = utils\CurrentUser::session();
        if ($session->isPasswordConfirmed()) {
            return Response::ok('my/security/show_confirmed.phtml');
        } else {
            return Response::ok('my/security/show_to_confirm.phtml');
        }
    }

    /**
     * Confirm the password is correct for the current session
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected
     * @response 302 /my/security
     * @flash error
     *    If CSRF is invalid
     * @response 302 /my/security
     * @flash errors
     *    If password is invalid
     * @response 302 /my/security
     *    On success
     */
    public function confirmPassword($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('security'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('security');
        }

        $password = $request->param('password');
        if (!$user->verifyPassword($password)) {
            utils\Flash::set('errors', [
                'password_hash' => _('The password is incorrect.'),
            ]);
            return Response::redirect('security');
        }

        $session_dao = new models\dao\Session();
        $session = utils\CurrentUser::session();
        $session->confirmed_password_at = \Minz\Time::now();
        $session_dao->save($session);

        return Response::redirect('security');
    }
}

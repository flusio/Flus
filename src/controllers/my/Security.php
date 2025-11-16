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
class Security extends BaseController
{
    /**
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws auth\PasswordNotConfirmedError
     *     If the password is not confirmed.
     */
    public function show(): Response
    {
        $user = auth\CurrentUser::require();

        auth\CurrentUser::requireConfirmedPassword();

        $form = new forms\security\Credentials(model: $user);

        return Response::ok('my/security/show.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Update email and password of the user.
     *
     * @request_param string email
     * @request_param string password
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /my/security
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws auth\PasswordNotConfirmedError
     *     If the password is not confirmed.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        auth\CurrentUser::requireConfirmedPassword();

        $form = new forms\security\Credentials(model: $user);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/security/show.phtml', [
                'form' => $form,
            ]);
        }

        $old_email = $user->email;
        $old_password_hash = $user->password_hash;

        $user = $form->model();
        $user->save();

        if ($user->email !== $old_email || $user->password_hash !== $old_password_hash) {
            // We make sure to clean token and sessions to prevent attacker to take
            // control back on the account
            if ($user->reset_token) {
                models\Token::delete($user->reset_token);
            }
            $session = auth\CurrentUser::session();
            models\Session::deleteByUserId($user->id, $session->id);
        }

        return Response::redirect('security');
    }

    /**
     * Show a form to confirm the password of the user. It is required to
     * perform some sensitive actions.
     *
     * @request_param string redirect_to
     *
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function confirmation(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\security\ConfirmPassword([
            'redirect_to' => $request->parameters->getString('redirect_to', ''),
        ], options: [
            'user' => $user,
        ]);

        return Response::ok('my/security/confirmation.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Confirm the password for the current session.
     *
     * @request_param string password
     * @request_param string redirect_to
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :redirect_to
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function confirm(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\security\ConfirmPassword(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/security/confirmation.phtml', [
                'form' => $form,
            ]);
        }

        $session = auth\CurrentUser::session();
        $session->confirmPassword();

        return Response::found($form->redirect_to);
    }
}

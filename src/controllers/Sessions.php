<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\forms;
use App\models;
use App\utils;

/**
 * Handle the requests related to the current session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions extends BaseController
{
    /**
     * Show the login form.
     *
     * @request_param string redirect_to
     *
     * @response 302 :redirect_to
     *     If the user is already connected.
     * @response 200
     */
    public function new(Request $request): Response
    {
        $redirect_to = $request->parameters->getString('redirect_to');

        $router = \Minz\Engine::router();
        if (!$redirect_to || !$router->isRedirectable($redirect_to)) {
            $redirect_to = \Minz\Url::for('home');
        }

        if (auth\CurrentUser::get()) {
            return Response::found($redirect_to);
        }

        $form = new forms\Login([
            'redirect_to' => $redirect_to,
        ]);

        return Response::ok('sessions/new.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Login / create a browser Session for the user.
     *
     * @request_param string csrf_token
     * @request_param string email
     * @request_param string password
     * @request_param string redirect_to
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :redirect_to
     *     On success.
     */
    public function create(Request $request): Response
    {
        $form = new forms\Login();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('sessions/new.phtml', [
                'form' => $form,
            ]);
        }

        $response = Response::found($form->redirect_to);

        if (!auth\CurrentUser::get()) {
            $user = $form->user();

            $session = auth\CurrentUser::createBrowserSession($user, $request);
            $session_token = $session->token();

            $response->setCookie('session_token', $session_token->token, [
                'expires' => $session_token->expired_at->getTimestamp(),
                'samesite' => 'Lax',
            ]);
        }

        return $response;
    }

    /**
     * Change the current locale.
     *
     * @request_param string csrf
     * @request_param string locale
     * @request_param string redirect_to A URL to redirect to (optional, default is `/`)
     *
     * @response 302 :redirect_to
     */
    public function changeLocale(Request $request): Response
    {
        $locale = $request->parameters->getString('locale', '');
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            return Response::found($redirect_to);
        }

        $available_locales = utils\Locale::availableLocales();
        if (isset($available_locales[$locale])) {
            $_SESSION['locale'] = $locale;
        } else {
            \Minz\Log::warning(
                "[Sessions#changeLocale] Tried to set invalid `{$locale}` locale."
            );
        }

        return Response::found($redirect_to);
    }

    /**
     * Delete the current user session and logout the user.
     *
     * @request_param string csrf
     *
     * @response 302 /
     */
    public function delete(Request $request): Response
    {
        $current_user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('home'));

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('home');
        }

        auth\CurrentUser::deleteSession();

        $response = Response::redirect('home');
        $response->removeCookie('session_token');
        return $response;
    }
}

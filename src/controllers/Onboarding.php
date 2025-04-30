<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Onboarding extends BaseController
{
    /**
     * Show an onboarding page.
     *
     * @request_param integer step
     *
     * @response 302 /login?redirect_to=/onboarding if not connected
     * @response 404 if step is out of bound
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('onboarding'));

        $step = $request->paramInteger('step', 1);
        if ($step < 1 || $step > 6) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok("onboarding/step{$step}.phtml");
    }

    /**
     * Update the locale of the current user
     *
     * @request_param string csrf
     * @request_param string locale
     *
     * @response 302 /login?redirect_to=/onboarding
     *     if the user is not connected
     * @response 302 /onboarding
     *     if the CSRF or locale are invalid
     * @response 302 /onboarding
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function updateLocale(Request $request): Response
    {
        $locale = $request->param('locale', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('onboarding'));

        $user->locale = trim($locale);

        $errors = $user->validate();
        if (\Minz\Csrf::validate($csrf) && !$errors) {
            $user->save();
            utils\Locale::setCurrentLocale($locale);
        }

        return Response::redirect('onboarding');
    }
}

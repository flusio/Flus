<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Onboarding
{
    /**
     * Show an onboarding page.
     *
     * @request_param integer step
     *
     * @response 302 /login?redirect_to if not connected
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('onboarding'),
            ]);
        }

        $step = intval($request->param('step', 1));
        if ($step < 1 || $step > 5) {
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
    public function updateLocale($request)
    {
        $csrf = new \Minz\CSRF();
        $locale = $request->param('locale');

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('onboarding'),
            ]);
        }

        $user->locale = trim($locale);

        $errors = $user->validate();
        if ($csrf->validateToken($request->param('csrf')) && !$errors) {
            $user->save();
            utils\Locale::setCurrentLocale($locale);
        }

        return Response::redirect('onboarding');
    }
}

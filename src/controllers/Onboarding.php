<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

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
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested step is out of the bounds.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $step = $request->parameters->getInteger('step', 1);
        if ($step < 1 || $step > 6) {
            throw new utils\PaginationOutOfBoundsError(
                "Requested step ({$step}) is out of bounds."
            );
        }

        $locale_form = new forms\users\Locale(model: $user);

        return Response::ok("onboarding/step{$step}.phtml", [
            'locale_form' => $locale_form,
        ]);
    }

    /**
     * Update the locale of the current user
     *
     * @request_param string locale
     * @request_param string csrf_token
     *
     * @response 302 /onboarding
     * @flash error
     *     If at least one of the parameters is invalid.
     * @response 302 /onboarding
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function updateLocale(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\users\Locale(model: $user);
        $form->handleRequest($request);

        if (!$form->validate()) {
            $error = implode(' ', $form->errors());
            \Minz\Flash::set('error', $error);

            return Response::redirect('onboarding');
        }

        $user = $form->model();
        $user->save();

        utils\Locale::setCurrentLocale($user->locale);

        return Response::redirect('onboarding');
    }
}

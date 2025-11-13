<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preferences extends BaseController
{
    /**
     * Show the preferences page.
     *
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\users\Preferences(model: $user);

        return Response::ok('my/preferences/edit.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Update the preferences of the current user.
     *
     * @request_param string locale
     * @request_param bool option_compact_mode
     * @request_param bool accept_contact
     * @request_param bool beta_enabled
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /my/preferences
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\users\Preferences(model: $user);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/preferences/edit.phtml', [
                'form' => $form,
            ]);
        }

        $user = $form->model();
        $user->save();

        utils\Locale::setCurrentLocale($user->locale);

        if ($form->beta_enabled) {
            $user->enableBeta();
        } else {
            $user->disableBeta();
        }

        return Response::redirect('preferences');
    }
}

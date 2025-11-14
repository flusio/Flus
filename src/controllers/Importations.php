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
class Importations extends BaseController
{
    /**
     * Delete an importation
     *
     * @request_param string id
     * @request_param string redirect_to
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :redirect_to
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the importation doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the importation.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $importation = models\Importation::requireFromRequest($request);

        auth\Access::require($user, 'delete', $importation);

        $form = new forms\importations\DeleteImportation();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found(utils\RequestHelper::from($request));
        }

        $importation->remove();

        return Response::found($form->redirect_to);
    }
}

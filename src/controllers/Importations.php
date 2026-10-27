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
     * Show the state of an importation.
     *
     * It renders a Turbo frame, refreshed until the importation is over.
     *
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the importation doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the importation.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $importation = models\Importation::requireFromRequest($request);

        auth\Access::require($user, 'view', $importation);

        return Response::ok('importations/show.html.twig', [
            'importation' => $importation,
        ]);
    }

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
            utils\Notification::error($form->error('@base'));
            return Response::found(utils\RequestHelper::from($request));
        }

        $importation->remove();

        return Response::found($form->redirect_to);
    }
}

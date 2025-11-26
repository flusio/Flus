<?php

namespace App\controllers\importations;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\jobs;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml extends BaseController
{
    /**
     * Display the Opml importation main page
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\importations\OpmlImportation(options: [
            'user' => $user,
        ]);

        return Response::ok('importations/opml/show.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Initialize a new Opml importation and register an OpmlImportator job.
     *
     * @request_param file opml
     * @request_param string csrf_token
     *
     * @response 400
     *    If the CSRF token is invalid or if an OPML import already exists.
     * @response 302 /opml
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function import(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\importations\OpmlImportation(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('importations/opml/show.html.twig', [
                'form' => $form,
            ]);
        }

        $importation = $form->importation();

        if (!$importation) {
            $form->addError(
                'opml',
                'failed_upload',
                _('This file cannot be uploaded.'),
            );

            return Response::internalServerError('importations/opml/show.html.twig', [
                'form' => $form,
            ]);
        }

        $importation->save();
        $importator_job = new jobs\OpmlImportator();
        $importator_job->performAsap($importation->id);

        return Response::redirect('opml');
    }
}

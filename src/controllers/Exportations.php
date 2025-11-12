<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\jobs;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Exportations extends BaseController
{
    /**
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\Exportation(options: [
            'user' => $user,
        ]);

        return Response::ok('exportations/show.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * @request_param string csrf_token
     *
     * @response 400
     *     If the CSRF token is invalid, or if an exportation is already ongoing.
     * @response 302 /exportations
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\Exportation(options: [
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('exportations/show.phtml', [
                'form' => $form,
            ]);
        }

        $exportation = $form->exportation();

        $exportation->save();

        $exportator_job = new jobs\Exportator();
        $exportator_job->performAsap($exportation->id);

        return Response::redirect('exportation');
    }

    /**
     * @response 404
     *     If there’s no archive to download.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function download(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $exportation = models\Exportation::findByUser($user);

        if (
            !$exportation ||
            !$exportation->isFinished() ||
            !file_exists($exportation->filepath)
        ) {
            return Response::notFound('not_found.phtml');
        }

        $filename_date = $exportation->created_at->format('Y-m-d');
        $filename_brand = \App\Configuration::$application['brand'];
        $filename = "{$filename_date}_{$filename_brand}_data.zip";

        $file_output = new \Minz\Output\File($exportation->filepath);
        $response = new Response(200, $file_output);
        $filesize = filesize($exportation->filepath);
        $response->setHeader('Content-Length', (string) $filesize);
        $response->setHeader('Content-Disposition', "filename={$filename}");
        return $response;
    }
}

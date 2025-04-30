<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\jobs;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Exportations extends BaseController
{
    /**
     * @response 302 /login?redirect_to=/exportations
     *     If user is not connected
     * @response 200
     *     On success
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('exportation'));

        $exportation = models\Exportation::findBy([
            'user_id' => $user->id,
        ]);

        return Response::ok('exportations/show.phtml', [
            'exportation' => $exportation,
        ]);
    }

    /**
     * @request_param string csrf
     *
     * @response 400
     *     If CSRF is invalid, or if an exportation is already ongoing
     * @response 302 /login?redirect_to=/exportations
     *     If user is not connected
     * @response 302 /exportations
     *     On success
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('exportation'));
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('exportations/show.phtml', [
                'exportation' => null,
                'error' => _('A security verification failed.'),
            ]);
        }

        $exportation = models\Exportation::findBy([
            'user_id' => $user->id,
        ]);
        if ($exportation && $exportation->status !== 'ongoing') {
            @unlink($exportation->filepath);
            models\Exportation::delete($exportation->id);
        } elseif ($exportation) {
            return Response::badRequest('exportations/show.phtml', [
                'exportation' => $exportation,
                'error' => _('You already have an ongoing exportation.'),
            ]);
        }

        $exportation = new models\Exportation($user->id);
        $exportation->save();
        $exportator_job = new jobs\Exportator();
        $exportator_job->performAsap($exportation->id);

        return Response::redirect('exportation');
    }

    /**
     * @response 302 /login?redirect_to=/exportations
     *     If user is not connected
     * @response 404
     *     If there’s no archive to download
     * @response 200
     *     On success
     */
    public function download(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('exportation'));

        $exportation = models\Exportation::findBy([
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        if (!$exportation || !file_exists($exportation->filepath)) {
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

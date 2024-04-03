<?php

namespace App\controllers\importations;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\jobs;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml
{
    /**
     * Display the Opml importation main page
     *
     * @response 302 /login?redirect_to=/opml
     *    If the user is not connected
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('opml'),
            ]);
        }

        $importation = models\Importation::findBy([
            'type' => 'opml',
            'user_id' => $user->id,
        ]);
        return Response::ok('importations/opml/show.phtml', [
            'importation' => $importation,
        ]);
    }

    /**
     * Initialize a new Opml importation and register an OpmlImportator job.
     *
     * @request_param string $csrf
     *
     * @response 302 /login?redirect_to=/opml
     *    If the user is not connected
     * @response 400
     *    If the CSRF token is invalid or if an import of opml type already exists
     * @response 302 /opml
     *    On success
     */
    public function import(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('opml'),
            ]);
        }

        $importation = models\Importation::findBy([
            'type' => 'opml',
            'user_id' => $user->id,
        ]);
        if ($importation) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => $importation,
                'error' => _('You already have an ongoing OPML importation.')
            ]);
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'error' => _('A security verification failed.'),
            ]);
        }

        $opml_file = $request->paramFile('opml');
        if (!$opml_file) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('The file is required.'),
                ],
            ]);
        }

        if ($opml_file->isTooLarge()) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('This file is too large.'),
                ],
            ]);
        } elseif ($opml_file->error) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => vsprintf(_('This file cannot be uploaded (error %d).'), [$opml_file->error]),
                ],
            ]);
        }

        $importations_filepath = \Minz\Configuration::$data_path . '/importations';
        if (!file_exists($importations_filepath)) {
            @mkdir($importations_filepath);
        }

        $opml_filepath = "{$importations_filepath}/opml_{$user->id}.xml";
        $is_moved = $opml_file->move($opml_filepath);
        if (!$is_moved) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('This file cannot be uploaded.'),
                ],
            ]);
        }

        $importation = new models\Importation('opml', $user->id, [
            'opml_filepath' => $opml_filepath,
        ]);
        $importation->save();
        $importator_job = new jobs\OpmlImportator();
        $importator_job->performAsap($importation->id);

        return Response::redirect('opml');
    }
}

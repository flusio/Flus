<?php

namespace flusio\controllers\importations;

use Minz\Response;
use flusio\auth;
use flusio\jobs;
use flusio\models;

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
    public function show($request)
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
     * @response 200
     */
    public function import($request)
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
        if ($importation) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => $importation,
                'error' => _('You already have an ongoing OPML importation.')
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'error' => _('A security verification failed.'),
            ]);
        }

        $uploaded_file = $request->param('opml');
        if (!isset($uploaded_file['error'])) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('The file is required.'),
                ],
            ]);
        }

        $error_status = $uploaded_file['error'];
        if (
            $error_status === UPLOAD_ERR_INI_SIZE ||
            $error_status === UPLOAD_ERR_FORM_SIZE
        ) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('This file is too large.'),
                ],
            ]);
        } elseif ($error_status !== UPLOAD_ERR_OK) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => vsprintf(_('This file cannot be uploaded (error %d).'), [$error_status]),
                ],
            ]);
        }

        $importations_filepath = \Minz\Configuration::$data_path . '/importations';
        if (!file_exists($importations_filepath)) {
            @mkdir($importations_filepath);
        }

        $opml_filepath = "{$importations_filepath}/opml_{$user->id}.xml";
        $is_moved = move_uploaded_file($uploaded_file['tmp_name'], $opml_filepath);
        if (!$is_moved) {
            return Response::badRequest('importations/opml/show.phtml', [
                'importation' => null,
                'errors' => [
                    'opml' => _('This file cannot be uploaded.'),
                ],
            ]);
        }

        $importation = models\Importation::init('opml', $user->id, [
            'opml_filepath' => $opml_filepath,
        ]);
        $importation->save();
        $importator_job = new jobs\OpmlImportator();
        $importator_job->performLater($importation->id);

        return Response::ok('importations/opml/show.phtml', [
            'importation' => $importation,
        ]);
    }
}

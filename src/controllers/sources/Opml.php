<?php

namespace App\controllers\sources;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\jobs;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml extends BaseController
{
    use \App\controllers\traits\OpmlResponder;

    /**
     * Show the sources followed by the current user as an OPML file.
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

        $opml_generator = new services\OpmlGenerator();
        $opml = $opml_generator->renderFollows($user, [
            'context_user' => $user,
            'with_time_filter' => true,
        ]);

        return $this->opmlResponse($opml, 'sources');
    }

    /**
     * Initialize a new OPML importation and register an OpmlImportator job.
     *
     * @request_param file opml
     * @request_param string csrf_token
     *
     * @response 302 /sources
     * @flash error
     *    If the CSRF token is invalid, if the file is invalid or cannot be
     *    uploaded, or if an OPML importation already exists.
     * @response 302 /sources
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
            utils\Notification::error($form->error('@base') ?: $form->error('opml'));
            return Response::redirect('sources');
        }

        $importation = $form->importation();

        if (!$importation) {
            utils\Notification::error(_('This file cannot be uploaded.'));
            return Response::redirect('sources');
        }

        $importation->save();
        $importator_job = new jobs\OpmlImportator();
        $importator_job->performAsap($importation->id);

        return Response::redirect('sources');
    }

    /**
     * Alias the old /opml URL to the sources page, where the OPML files are imported.
     *
     * @response 301 /sources
     */
    public function alias(Request $request): Response
    {
        $url = \Minz\Url::for('sources');
        return Response::movedPermanently($url);
    }
}

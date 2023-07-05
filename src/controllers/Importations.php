<?php

namespace flusio\controllers;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Importations
{
    /**
     * Delete an importation
     *
     * @request_param string id
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the importation doesn’t exist or user hasn't access
     * @response 302 :from if csrf is invalid
     * @response 302 /collections on success
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $importation_id = $request->param('id', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $importation = models\Importation::findBy([
            'id' => $importation_id,
            'user_id' => $user->id,
        ]);
        if (!$importation) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $importation_type = $importation->type;

        models\Importation::delete($importation->id);

        if ($importation_type === 'pocket') {
            return Response::redirect('links');
        } else {
            return Response::redirect('feeds');
        }
    }
}

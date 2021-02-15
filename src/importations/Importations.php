<?php

namespace flusio\importations;

use Minz\Response;
use flusio\models;
use flusio\utils;

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
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the importation doesn’t exist or user hasn't access
     * @response 302 :from if csrf is invalid
     * @response 302 /collections on success
     */
    public function delete($request)
    {
        $user = utils\CurrentUser::get();
        $importation_id = $request->param('id');
        $from = $request->param('from');

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

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Importation::delete($importation->id);

        return Response::redirect('collections');
    }
}

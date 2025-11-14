<?php

namespace App\controllers\api\v1\links;

use App\auth;
use App\controllers\api\v1\BaseController;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Later extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user doesn't have access to the link.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $link_id = $request->parameters->getString('id', '');
        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot update the link.',
            ]);
        }

        $user->markAsReadLater($link);

        return Response::json(200, []);
    }
}

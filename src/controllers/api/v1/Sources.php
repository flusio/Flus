<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sources extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot access the collection.
     * @response 404
     *     If the collection does not exist.
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $source_id = $request->parameters->getString('id', '');
        $source = models\Source::find($source_id);

        if (!$source) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (!auth\Access::can($user, 'view', $source)) {
            return Response::json(403, [
                'error' => 'You cannot access the collection.',
            ]);
        }

        return Response::json(200, $source->toJson(context_user: $user));
    }
}

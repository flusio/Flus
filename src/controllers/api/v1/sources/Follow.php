<?php

namespace App\controllers\api\v1\sources;

use App\auth;
use App\controllers\api\v1\BaseController;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Follow extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user doesn't have access to the source.
     * @response 404
     *     If the source does not exist.
     * @response 200
     */
    public function create(Request $request): Response
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
                'error' => 'You cannot follow the collection.',
            ]);
        }

        $is_following = $user->isFollowing($source);
        if (!$is_following) {
            $user->follow($source);
        }

        return Response::json(200, []);
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 404
     *     If the source does not exist.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $source_id = $request->parameters->getString('id', '');
        $source = models\Source::find($source_id);

        if (!$source) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        // We don't check if the user has access to the source here.
        // This is to prevent the case where the user has lost the access to
        // the source, but wouldn't be able to unfollow it.

        $is_following = $user->isFollowing($source);
        if ($is_following) {
            $user->unfollow($source);
        }

        return Response::json(200, []);
    }
}

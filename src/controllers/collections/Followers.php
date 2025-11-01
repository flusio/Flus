<?php

namespace App\controllers\collections;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Followers extends BaseController
{
    /**
     * Make the current user following the given collection.
     *
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\FollowCollection();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($collection->id);
        if (!$is_following) {
            $user->follow($collection->id);
        }

        return Response::found($from);
    }

    /**
     * Make the current user unfollowing the given collection.
     *
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\UnfollowCollection();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($collection->id);
        if ($is_following) {
            $user->unfollow($collection->id);
        }

        return Response::found($from);
    }
}

<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Followers
{
    /**
     * Make the current user following the given collection
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or user hasn't access
     * @response 302 :from
     *     if CSRF is invalid
     * @response 302 :from
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (!$collection || !$collection->is_public) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($collection->id);
        if (!$is_following) {
            $user->follow($collection->id);
        }

        return Response::found($from);
    }

    /**
     * Make the current user following the given collection
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or user hasn't access
     * @response 302 :from
     *     if CSRF is invalid
     * @response 302 :from
     */
    public function delete($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (!$collection || !$collection->is_public) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($collection->id);
        if ($is_following) {
            $user->unfollow($collection->id);
        }

        return Response::found($from);
    }
}

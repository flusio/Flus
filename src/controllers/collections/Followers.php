<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Followers extends BaseController
{
    /**
     * Make the current user following the given collection
     *
     * @request_param string id
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or user hasn't access
     * @response 302 :from
     *     if CSRF is invalid
     * @response 302 :from
     */
    public function create(Request $request): Response
    {
        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        $can_view = $collection && auth\CollectionsAccess::canView($user, $collection);
        if (!$can_view) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
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
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist
     * @response 302 :from
     *     if CSRF is invalid
     * @response 302 :from
     */
    public function delete(Request $request): Response
    {
        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        if (!$collection) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($collection->id);
        if ($is_following) {
            $user->unfollow($collection->id);
        }

        return Response::found($from);
    }
}

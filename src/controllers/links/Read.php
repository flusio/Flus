<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read
{
    /**
     * Mark a link as read and remove it from bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        $link_id = $request->param('id');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        models\LinkToCollection::markAsRead($user, [$link->id]);

        return Response::found($from);
    }

    /**
     * Remove a link from news and add it to bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function later($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        $link_id = $request->param('id');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        models\LinkToCollection::markToReadLater($user, [$link->id]);

        return Response::found($from);
    }
}

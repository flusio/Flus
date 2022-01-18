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
class Read
{
    /**
     * Mark links of the collection as read and remove them from bookmarks.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
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
        $csrf = $request->param('csrf');
        $collection_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        $links = [];
        if (auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links();
        } elseif ($user->isFollowing($collection->id)) {
            $collection_links = $collection->links([], ['hidden' => false]);
            $links = $user->obtainLinks($collection_links);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->created_at) {
                    $link->created_at = \Minz\Time::now();
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link_ids = array_column($links, 'id');
        models\LinkToCollection::markAsRead($user, $link_ids);

        return Response::found($from);
    }

    /**
     * Remove links of the collection from news and add them to bookmarks.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
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
        $csrf = $request->param('csrf');
        $collection_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        $links = [];
        if (auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links();
        } elseif ($user->isFollowing($collection->id)) {
            $collection_links = $collection->links([], ['hidden' => false]);
            $links = $user->obtainLinks($collection_links);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->created_at) {
                    $link->created_at = \Minz\Time::now();
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link_ids = array_column($links, 'id');
        models\LinkToCollection::markToReadLater($user, $link_ids);

        return Response::found($from);
    }

    /**
     * Remove links of the collection from news and bookmarks and add them to the never list.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function never($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        $csrf = $request->param('csrf');
        $collection_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        $links = [];
        if (auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links();
        } elseif ($user->isFollowing($collection->id)) {
            $collection_links = $collection->links([], ['hidden' => false]);
            $links = $user->obtainLinks($collection_links);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->created_at) {
                    $link->created_at = \Minz\Time::now();
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link_ids = array_column($links, 'id');
        models\LinkToCollection::markToNeverRead($user, $link_ids);

        return Response::found($from);
    }
}

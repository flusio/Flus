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
     * Show the read page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/read
     *     if not connected
     * @response 302 /read?page=:bounded_page
     *     if page is invalid
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('read list'),
            ]);
        }

        $read_list = $user->readList();
        $number_links = models\Link::daoCall('countByCollectionId', $read_list->id);
        $pagination_page = $request->paramInteger('page', 1);
        $number_per_page = 30;
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('read list', [
                'page' => $pagination->currentPage(),
            ]);
        }

        return Response::ok('read/index.phtml', [
            'collection' => $read_list,
            'links' => $read_list->links(
                ['published_at', 'number_comments'],
                [
                    'offset' => $pagination->currentOffset(),
                    'limit' => $pagination->numberPerPage()
                ]
            ),
            'pagination' => $pagination,
        ]);
    }

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
            $links = $collection->links([]);
        } elseif ($user->isFollowing($collection->id)) {
            // This loop is not efficient since the collection may contain a
            // lot of links. If it becomes an issue, it could be fixed by
            // getting the differnce between the collection URLs and the user'
            // URLs. The result could then be inserted in DB with a bulk
            // operation.
            // See also similar loops in later() and never() methods.
            foreach ($collection->links([], ['hidden' => false]) as $link) {
                $new_link = $user->obtainLink($link);
                if (!$new_link->created_at) {
                    $new_link->save();
                }
                $links[] = $new_link;
            }
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
            $links = $collection->links([]);
        } elseif ($user->isFollowing($collection->id)) {
            foreach ($collection->links([], ['hidden' => false]) as $link) {
                $new_link = $user->obtainLink($link);
                if (!$new_link->created_at) {
                    $new_link->save();
                }
                $links[] = $new_link;
            }
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
            $links = $collection->links([]);
        } elseif ($user->isFollowing($collection->id)) {
            foreach ($collection->links([], ['hidden' => false]) as $link) {
                $new_link = $user->obtainLink($link);
                if (!$new_link->created_at) {
                    $new_link->save();
                }
                $links[] = $new_link;
            }
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

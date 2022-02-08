<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections
{
    /**
     * Show the page to update the link collections
     *
     * @request_param string id
     * @request_param string mode Either 'normal' (default), 'adding' or 'news'
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $mode = $request->param('mode', 'normal');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (auth\LinksAccess::canUpdate($user, $link)) {
            $collection_ids = array_column($link->collections(), 'id');
        } else {
            $collection_ids = [];
        }

        if ($mode === 'news') {
            $collections = $user->collections();
            utils\Sorter::localeSort($collections, 'name');

            return Response::ok('links/collections/index_news.phtml', [
                'link' => $link,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'comment' => '',
                'from' => $from,
            ]);
        } elseif ($mode === 'adding') {
            $bookmarks = $user->bookmarks();
            $collections = $user->collections();
            utils\Sorter::localeSort($collections, 'name');
            $collections = array_merge([$bookmarks], $collections);

            return Response::ok('links/collections/index_adding.phtml', [
                'link' => $link,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
            ]);
        } else {
            $bookmarks = $user->bookmarks();
            $collections = $user->collections();
            utils\Sorter::localeSort($collections, 'name');
            $collections = array_merge([$bookmarks], $collections);

            return Response::ok('links/collections/index.phtml', [
                'link' => $link,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
            ]);
        }
    }

    /**
     * Update the link collections list
     *
     * News mode allows to set is_hidden and add a comment. It also removes the
     * link from the news and from bookmarks and adds it to the read list.
     * Adding mode allows to set is_hidden.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param boolean is_hidden
     * @request_param string comment
     * @request_param string mode Either 'normal' (default), 'adding' or 'news'
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 302 :from if CSRF or collection_ids are invalid
     * @response 302 :from
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->paramArray('collection_ids', []);
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $comment = trim($request->param('comment', ''));
        $mode = $request->param('mode', 'normal');
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!models\Collection::daoCall('doesUserOwnCollections', $user->id, $new_collection_ids)) {
            utils\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            $link = $user->obtainLink($link);

            list($via_type, $via_resource_id) = $this->extractViaFromPath($from);
            if ($via_type) {
                $link->via_type = $via_type;
                $link->via_resource_id = $via_resource_id;
            }

            $link->save();
        }

        models\LinkToCollection::setCollections($link->id, $new_collection_ids);

        if ($mode === 'adding') {
            $link->is_hidden = $is_hidden;
            $link->save();
        } elseif ($mode === 'news') {
            $link->is_hidden = $is_hidden;
            $link->save();

            if ($comment) {
                $message = models\Message::init($user->id, $link->id, $comment);
                $message->save();
            }

            models\LinkToCollection::markAsRead($user, [$link->id]);
        }

        return Response::found($from);
    }

    /**
     * Return the via type and resource id from a path.
     *
     * For instance:
     *
     * - For the path `/collections/1234567890`, ['collection', '1234567890']
     *   will be returned (if the collection exists in db)
     * - For the path `/p/1234567890`, ['user', '1234567890'] will be
     *   returned (if the user exists in db)
     * - For other paths, ['', null] will be returned
     *
     * @param string $path
     *
     * @return string[]
     */
    public function extractViaFromPath($path)
    {
        $matches = [];

        $result = preg_match('#^/collections/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            $collection_id = $matches['id'];

            if (!models\Collection::exists($collection_id)) {
                return ['', null];
            }

            return ['collection', $collection_id];
        }

        $result = preg_match('#^/p/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            $user_id = $matches['id'];

            if (!models\User::exists($user_id)) {
                return ['', null];
            }

            return ['user', $user_id];
        }

        return ['', null];
    }
}

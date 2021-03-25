<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLinkRemovals
{
    /**
     * Allow to add a link from a news_link (which is mark as read). If a link
     * already exists with the same URL, it is offered to update it.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news/:id/add
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not associated to the current user
     * @response 200
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function adding($request)
    {
        $user = utils\CurrentUser::get();
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('add news', ['id' => $news_link_id]),
            ]);
        }

        $news_link = $user->newsLink($news_link_id);
        if (!$news_link) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections(true);
        models\Collection::sort($collections, $user->locale);

        $existing_link = $user->linkByUrl($news_link->url);
        if ($existing_link) {
            $is_hidden = $existing_link->is_hidden;
            $existing_collections = $existing_link->collections();
            $collection_ids = array_column($existing_collections, 'id');
        } else {
            $is_hidden = false;
            $collection_ids = [];
        }

        return Response::ok('news_link_removals/adding.phtml', [
            'news_link' => $news_link,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'collections' => $collections,
            'comment' => '',
            'exists_already' => $existing_link !== null,
        ]);
    }

    /**
     * Mark a news_link as read and add it as a link to the user's collections.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param boolean is_hidden
     * @request_param string[] collection_ids
     * @request_param string comment
     *
     * @response 302 /login?redirect_to=/news/:id/add
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not associated to the current user
     * @response 400
     *     if CSRF is invalid, if collection_ids is empty or contains inexisting ids
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function add($request)
    {
        $user = utils\CurrentUser::get();
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('add news', ['id' => $news_link_id]),
            ]);
        }

        $news_link = $user->newsLink($news_link_id);
        if (!$news_link) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections(true);
        models\Collection::sort($collections, $user->locale);

        $existing_link = $user->linkByUrl($news_link->url);

        $is_hidden = $request->param('is_hidden', false);
        $collection_ids = $request->param('collection_ids', []);
        $comment = $request->param('comment', '');

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('news_link_removals/adding.phtml', [
                'news_link' => $news_link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'comment' => $comment,
                'exists_already' => $existing_link !== null,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if (empty($collection_ids)) {
            return Response::badRequest('news_link_removals/adding.phtml', [
                'news_link' => $news_link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'comment' => $comment,
                'exists_already' => $existing_link !== null,
                'errors' => [
                    'collection_ids' => _('The link must be associated to a collection.'),
                ],
            ]);
        }

        if (!models\Collection::daoCall('existForUser', $user->id, $collection_ids)) {
            return Response::badRequest('news_link_removals/adding.phtml', [
                'news_link' => $news_link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'comment' => $comment,
                'exists_already' => $existing_link !== null,
                'errors' => [
                    'collection_ids' => _('One of the associated collection doesn’t exist.'),
                ],
            ]);
        }

        // First, save the link (if a Link with matching URL exists, just get
        // this link and optionally change its is_hidden status)
        if ($existing_link) {
            $link = $existing_link;
        } else {
            $link = models\Link::initFromNews($news_link, $user->id);
        }
        $link->is_hidden = filter_var($is_hidden, FILTER_VALIDATE_BOOLEAN);
        $link->save();

        // Attach the link to the given collections (and potentially forget the
        // old ones)
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $links_to_collections_dao->set($link->id, $collection_ids);

        // Then, if a comment has been passed, save it.
        if (trim($comment)) {
            $message = models\Message::init($user->id, $link->id, $comment);
            $message->save();
        }

        // Finally, mark the news_link as read.
        $news_link->is_read = true;
        $news_link->save();

        return Response::redirect('news');
    }

    /**
     * Mark a news link as read and remove it from bookmarks.
     *
     * @request_param string csrf
     * @request_param string id (can be "all")
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function markAsRead($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        if ($news_link_id === 'all') {
            $news_links = $user->newsLinks();
        } else {
            $news_link = $user->newsLink($news_link_id);
            if (!$news_link) {
                utils\Flash::set('error', _('The link doesn’t exist.'));
                return Response::found($from);
            }
            $news_links = [$news_link];
        }

        $bookmarks = $user->bookmarks();

        foreach ($news_links as $news_link) {
            // First, we mark the news link as read
            $news_link->is_read = true;
            $news_link->save();

            // Then, we try to find a link with corresponding URL in order to
            // remove it from bookmarks.
            $link = $user->linkByUrl($news_link->url);
            if ($link) {
                $actual_collection_ids = array_column($link->collections(), 'id');
                if (in_array($bookmarks->id, $actual_collection_ids)) {
                    $links_to_collections_dao = new models\dao\LinksToCollections();
                    $links_to_collections_dao->detach($link->id, [$bookmarks->id]);
                }
            }
        }

        return Response::found($from);
    }

    /**
     * Remove a link from news and add it to bookmarks.
     *
     * @request_param string csrf
     * @request_param string id (can be "all")
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function readLater($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        if ($news_link_id === 'all') {
            $news_links = $user->newsLinks();
        } else {
            $news_link = $user->newsLink($news_link_id);
            if (!$news_link) {
                utils\Flash::set('error', _('The link doesn’t exist.'));
                return Response::found($from);
            }
            $news_links = [$news_link];
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();

        foreach ($news_links as $news_link) {
            // First, we want the link with corresponding URL to exist for the
            // current user (or it would be impossible to bookmark it correctly).
            // If it doesn't exist, let's create it in DB from the $news_link variable.
            $link = $user->linkByUrl($news_link->url);
            if (!$link) {
                $link = models\Link::initFromNews($news_link, $user->id);
                $link->save();
            }

            // Then, we check if the link is bookmarked. If it isn't, bookmark it.
            $bookmarks = $user->bookmarks();
            $actual_collection_ids = array_column($link->collections(), 'id');
            if (!in_array($bookmarks->id, $actual_collection_ids)) {
                $links_to_collections_dao->attach($link->id, [$bookmarks->id]);
            }

            // Then, delete the news (we don't set the is_removed or it would no
            // longer be suggested to the user).
            models\NewsLink::delete($news_link->id);
        }

        return Response::found($from);
    }

    /**
     * Remove a link from news and bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function remove($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $news_link = $user->newsLink($news_link_id);
        if (!$news_link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();

        // First, remove the link from the news.
        $news_link->is_removed = true;
        $news_link->save();

        // Then, we try to find a link with corresponding URL in order to
        // remove it from bookmarks.
        $link = $user->linkByUrl($news_link->url);
        if ($link) {
            $bookmarks = $user->bookmarks();
            $actual_collection_ids = array_column($link->collections(), 'id');
            if (in_array($bookmarks->id, $actual_collection_ids)) {
                $links_to_collections_dao->detach($link->id, [$bookmarks->id]);
            }
        }

        return Response::found($from);
    }
}

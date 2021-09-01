<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class News
{
    /**
     * Show the news page.
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 200
     */
    public function show()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $news = $user->news();
        return Response::ok('news/show.phtml', [
            'links' => $news->links(),
            'has_collections' => count($user->collections(true)) > 0,
            'no_news' => utils\Flash::pop('no_news'),
        ]);
    }

    /**
     * Fill the news page with links to read (from bookmarks and followed
     * collections)
     *
     * @request_param string csrf
     * @request_param string type
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 400
     *     if csrf is invalid
     * @response 302 /news
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $type = $request->param('type');
        $csrf = $request->param('csrf');

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('news/show.phtml', [
                'links' => [],
                'has_collections' => count($user->collections(true)) > 0,
                'no_news' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($type === 'newsfeed') {
            $options = [
                'number_links' => 9,
                'until' => \Minz\Time::ago(3, 'days'),
                'from' => 'followed',
            ];
        } elseif ($type === 'short') {
            $options = [
                'number_links' => 3,
                'max_duration' => 10,
                'from' => 'bookmarks',
            ];
        } else {
            $options = [
                'number_links' => 1,
                'min_duration' => 10,
                'from' => 'bookmarks',
            ];
        }

        $news_picker = new services\NewsPicker($user, $options);
        $db_links = $news_picker->pick();

        $news = $user->news();

        foreach ($db_links as $db_link) {
            $news_link = new models\Link($db_link);
            $existing_link = models\Link::findBy([
                'user_id' => $user->id,
                'url' => $news_link->url,
            ]);

            if ($news_link->user_id === $user->id) {
                // This is our link, just work with it!
                $link = $news_link;
            } elseif ($existing_link) {
                // The news link is owned by another user, but we have a link
                // with the same URL, so let's work with this one.
                $link = $existing_link;
            } else {
                // The news link is owned by another user and we don't have a
                // link with the same URL, so we copy the news link for our
                // current user.
                $link = models\Link::copy($news_link, $user->id);
            }

            // Let's update the "via" info which will be used on the news page
            $link->via_type = $news_link->via_type;
            $link->via_link_id = $news_link->id;
            $link->via_collection_id = $news_link->via_collection_id;
            $link->save();

            // And don't forget to add the link to the news collection!
            models\LinkToCollection::attach($link->id, [$news->id], $news_link->published_at);
        }

        if (!$db_links) {
            utils\Flash::set('no_news', true);
        }

        return Response::redirect('news');
    }

    /**
     * Mark a news link as read and remove it from bookmarks.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     */
    public function markAsRead($request)
    {
        $user = auth\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $news = $user->news();
        $link_ids = array_column($news->links(), 'id');
        models\LinkToCollection::markAsRead($user, $link_ids);

        return Response::found($from);
    }

    /**
     * Remove a link from news and add it to bookmarks.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     */
    public function readLater($request)
    {
        $user = auth\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $news = $user->news();
        $link_ids = array_column($news->links(), 'id');
        models\LinkToCollection::markToReadLater($user, $link_ids);

        return Response::found($from);
    }
}

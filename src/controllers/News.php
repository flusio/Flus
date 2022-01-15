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
            'news' => $news,
            'links' => $news->links(['published_at', 'number_comments']),
            'has_collections' => count($user->collections()) > 0,
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

        $news = $user->news();

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('news/show.phtml', [
                'news' => $news,
                'links' => [],
                'has_collections' => count($user->collections()) > 0,
                'no_news' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($type === 'newsfeed') {
            $options = [
                'number_links' => 9,
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
            $link = $user->obtainLink($news_link);

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
}

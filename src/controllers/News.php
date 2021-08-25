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

        $links = models\Link::daoToList('listForNews', $user->id);

        return Response::ok('news/show.phtml', [
            'links' => $links,
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
        foreach ($db_links as $db_link) {
            $link = new models\Link($db_link);
            $news_link = models\NewsLink::initFromLink($link, $user->id);
            $news_link->save();
        }

        if (!$db_links) {
            utils\Flash::set('no_news', true);
        }

        return Response::redirect('news');
    }
}

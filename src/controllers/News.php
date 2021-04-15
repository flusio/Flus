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

        $news_links = models\NewsLink::daoToList('listComputedByUserId', $user->id);

        return Response::ok('news/show.phtml', [
            'news_links' => $news_links,
            'news_preferences' => models\NewsPreferences::fromJson($user->news_preferences),
            'has_collections' => count($user->collections(true)) > 0,
            'no_news' => utils\Flash::pop('no_news'),
        ]);
    }

    /**
     * Fill the news page with links to read (from bookmarks and followed
     * collections)
     *
     * @request_param string csrf
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

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('news/show.phtml', [
                'news_links' => [],
                'news_preferences' => models\NewsPreferences::fromJson($user->news_preferences),
                'has_collections' => count($user->collections(true)) > 0,
                'no_news' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $news_picker = new services\NewsPicker($user);
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

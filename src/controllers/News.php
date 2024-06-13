<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\services;
use App\utils;

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
    public function index(): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $news = $user->news();
        $links = $news->links(['published_at', 'number_comments']);
        $links_timeline = new utils\LinksTimeline($links);

        return Response::ok('news/index.phtml', [
            'news' => $news,
            'links_timeline' => $links_timeline,
            'no_news' => \Minz\Flash::pop('no_news'),
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
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $csrf = $request->param('csrf', '');

        $news = $user->news();

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('news/index.phtml', [
                'news' => $news,
                'links_timeline' => new utils\LinksTimeline([]),
                'no_news' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $news_picker = new services\NewsPicker($user, [
            'number_links' => 50,
        ]);
        $links = $news_picker->pick();

        $news = $user->news();

        foreach ($links as $news_link) {
            $link = $user->obtainLink($news_link);

            // If the link has already a source info, we want to keep it (it
            // might have been get via a followed collection, and put in the
            // bookmarks then)
            if (!$link->source_type && $news_link->source_news_type !== null) {
                $link->source_type = $news_link->source_news_type;
                $link->source_resource_id = $news_link->source_news_resource_id;
            }

            // Make sure to reset this value: it will be set to true later with
            // Link::groupLinksBySources
            $link->group_by_source = false;

            $link->save();

            // And don't forget to add the link to the news collection!
            models\LinkToCollection::attach([$link->id], [$news->id], $news_link->published_at);
        }

        models\Link::groupLinksBySources($news->id);

        if (!$links) {
            \Minz\Flash::set('no_news', true);
        }

        return Response::redirect('news');
    }

    public function showAvailable(Request $request): Response
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $news_picker = new services\NewsPicker($user, [
            'number_links' => 1,
            'from' => 'followed',
        ]);
        $links = $news_picker->pick();

        return Response::json(200, [
            'available' => count($links) > 0,
        ]);
    }
}

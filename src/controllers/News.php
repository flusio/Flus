<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class News extends BaseController
{
    /**
     * Show the news page.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(): Response
    {
        $user = auth\CurrentUser::require();

        $news = $user->news();
        $links = $news->links(['published_at', 'number_notes']);
        $links_timeline = new utils\LinksTimeline($links);

        $form = new forms\FillNews();

        return Response::ok('news/index.html.twig', [
            'news' => $news,
            'links_timeline' => $links_timeline,
            'no_news' => \Minz\Flash::pop('no_news'),
            'form' => $form,
        ]);
    }

    /**
     * Fill the news page with links to read (from bookmarks and followed
     * collections).
     *
     * @request_param string csrf_token
     *
     * @response 400
     *     If the CSRF token is invalid.
     * @response 302 /news
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\FillNews();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('news/index.html.twig', [
                'news' => $user->news(),
                'links_timeline' => new utils\LinksTimeline([]),
                'no_news' => false,
                'form' => $form,
            ]);
        }

        $journal = new models\Journal($user);
        $count = $journal->fill(max: 50);

        if ($count === 0) {
            \Minz\Flash::set('no_news', true);
        }

        return Response::redirect('news');
    }

    /**
     * Return a JSON telling if there are new links available for the news.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function showAvailable(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        return Response::json(200, [
            'available' => models\Link::anyFromFollowedCollections($user->id),
        ]);
    }
}
